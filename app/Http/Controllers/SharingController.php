<?php

namespace App\Http\Controllers;

use App\Enums\CounselingResolution;
use App\Enums\NlpAnalysisStatus;
use App\Enums\Priority;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Requests\CreateSharingRequest;
use App\Http\Requests\GetSharingRequest;
use App\Http\Requests\ReplySharingRequest;
use App\Http\Resources\SharingResource;
use App\Jobs\ProcessNlpAnalysisJob;
use App\Models\Sharing;
use App\Models\User;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharingController extends Controller
{
    use ApiResponder;

    /**
     * Get all sharing data
     *
     * Mendapatkan semua data curhatan milik siswa tersebut atau siswa yang dibawahi oleh BK tersebut. Hanya bisa diakses oleh BK dan siswa
     */
    #[Group('Sharing')]
    public function index(GetSharingRequest $request): JsonResponse
    {
        $user = Auth::user();
        if ($user->role == UserRole::STUDENT->value) {
            $query = $user->sharing()->with(['user.room', 'nlp', 'counseling'])->orderBy('replied_at');
        } elseif ($user->role == UserRole::COUNSELOR->value) {
            $query = Sharing::with(['user.room', 'nlp', 'counseling'])->whereIn('user_id', $user->counselored->pluck('id'));
        } else {
            return $this->success(SharingResource::collection(collect()));
        }

        // Filter: user.room (room.level + room.name, e.g. "XII IPA 4")
        $room = $request->input('room', $request->input('room_name'));
        if (!empty($room)) {
            $room = trim($room);
            $concatExpr = match (DB::connection()->getDriverName()) {
                'sqlite' => "level || ' ' || name",
                default => "CONCAT(level, ' ', name)",
            };

            $query->whereHas('user.room', function ($q) use ($room, $concatExpr) {
                $q->where(function ($sq) use ($room, $concatExpr) {
                    $sq->whereRaw("{$concatExpr} LIKE ?", ["%{$room}%"])
                       ->orWhere('name', 'like', "%{$room}%");
                });
            });
        }

        // Filter: status
        if ($request->filled('status')) {
            $status = strtolower(trim($request->input('status')));
            $enumCase = match ($status) {
                'belum ditinjau', 'belum_ditinjau', 'menunggu tinjauan', 'menunggu_tinjauan', 'pending' => ReportStatus::MENUNGGU_TINJAUAN,
                'sedang ditangani', 'sedang_ditangani', 'ditinjau' => ReportStatus::DITINJAU,
                'belum ditanggapi', 'belum_ditanggapi', 'menunggu tanggapan', 'menunggu_tanggapan' => ReportStatus::MENUNGGU_TANGGAPAN,
                'menunggu persetujuan siswa', 'menunggu_persetujuan_siswa', 'menunggu persetujuan', 'menunggu_persetujuan' => ReportStatus::MENUNGGU_PERSETUJUAN,
                'konseling dijadwalkan', 'konseling_dijadwalkan', 'dijadwalkan' => ReportStatus::DIJADWALKAN,
                'diselesaikan', 'selesai' => ReportStatus::SELESAI,
                'dibatalkan' => ReportStatus::DIBATALKAN,
                'jadwal ditolak siswa', 'jadwal_ditolak_siswa', 'ditolak' => ReportStatus::DITOLAK,
                'bukan urgent', 'bukan_urgent' => ReportStatus::BUKAN_URGENT,
                default => ReportStatus::tryFrom($request->input('status')),
            };

            if ($enumCase) {
                $query->where('status', $enumCase->value);
            } else {
                $query->where('status', $request->input('status'));
            }
        }

        // Filter: priority
        if ($request->filled('priority')) {
            $priority = strtolower(trim($request->input('priority')));
            if ($priority === 'kritis' || $priority === Priority::TINGGI->value) {
                $query->where('priority', Priority::TINGGI->value);
            } elseif ($priority === Priority::SEDANG->value) {
                $query->where('priority', Priority::SEDANG->value);
            } elseif ($priority === Priority::RENDAH->value) {
                $query->where('priority', Priority::RENDAH->value);
            } elseif ($priority === 'prioritas') {
                $query->whereIn('priority', [Priority::RENDAH->value, Priority::SEDANG->value]);
            } else {
                $priorityEnum = Priority::tryFrom($priority);
                if ($priorityEnum) {
                    $query->where('priority', $priorityEnum->value);
                } else {
                    $query->where('priority', $priority);
                }
            }
        }

        $sharings = $query->get();

        return $this->success(SharingResource::collection($sharings));
    }

    /**
     * Get sharing count by types
     *
     * Mendapatkan jumlah curhatan hari itu berdasarkan tipe. Hanya bisa diakses oleh BK
     */
    #[Group('Sharing')]
    #[ExcludeRouteFromDocs]
    public function getSharingCount()
    {
        Gate::authorize('viewGraph', Sharing::class);
        $user = Auth::user();
        $sharings = Sharing::whereDate('created_at', Carbon::today())
            ->whereIn('user_id', $user->counselored->pluck('id'));

        $received = (clone $sharings)->whereNull('reply')->count();
        $replied = (clone $sharings)->whereNotNull('reply')->count();

        return $this->success([
            'received' => $received,
            'replied' => $replied,
            'total' => $replied + $received,
        ]);
    }

    /**
     * Create new sharing
     *
     * Membuat curhatan baru dan hanya bisa dilakukan oleh siswa. Secara default prioritasnya adalah rendah
     */
    #[Group('Sharing')]
    public function store(CreateSharingRequest $request)
    {
        $sharing = Sharing::create($request->all());

        $nlpAnalysis = $sharing->nlp()->create([
            'text' => $sharing->description,
        ]);
        try {
            ProcessNlpAnalysisJob::dispatchSync($nlpAnalysis);
        } catch (\Throwable $th) {
            ProcessNlpAnalysisJob::dispatch($nlpAnalysis);
        }

        return $this->created(new SharingResource($sharing->load(['nlp', 'counseling'])));
    }

    /**
     * Get sharing detail
     *
     * Mendapatkan detail curhatan. Hanya bisa diakses oleh murid, Super Admin, atau BK dari murid tersebut
     */
    #[Group('Sharing')]
    public function show(Sharing $sharing)
    {
        Gate::authorize('view', $sharing);

        return $this->success(new SharingResource($sharing->load(['nlp', 'counseling'])));
    }

    /**
     * Get sharing of student
     *
     * Mendapatkan semua curhatan siswa. Hanya bisa diakses oleh super admin
     */
    #[Group('Sharing')]
    #[ExcludeRouteFromDocs]
    public function sharingOfStudent(User $user)
    {
        Gate::authorize('viewStudentSharing', [Sharing::class, $user]);
        $sharings = Sharing::whereUserId($user->id)->with(['nlp', 'counseling'])->get();

        return $this->success(SharingResource::collection($sharings));
    }

    /**
     * Reply to the sharing
     *
     * Membalas curhatan siswa dan hanya bisa dilakukan oleh Guru BK siswa tersebut
     */
    #[Group('Sharing')]
    public function reply(ReplySharingRequest $request, Sharing $sharing)
    {
        $sharing->update($request->all());

        return $this->success(new SharingResource($sharing->load(['nlp', 'counseling'])));
    }

    /**
     * Acknowledge the sharing
     *
     * Meninjau curhatan siswa dan hanya bisa dilakukan oleh Guru BK siswa tersebut
     */
    #[Group('Sharing')]
    public function acknowledge(Sharing $sharing)
    {
        $sharing->update([
            'status' => ReportStatus::DITINJAU->value,
            'acknowledged_at' => now(),
        ]);

        return $this->success(new SharingResource($sharing->load(['nlp', 'counseling'])));
    }

    /**
     * Sign false positive
     *
     * Menandai curhatan sebagai false positive dan hanya bisa dilakukan oleh Guru BK siswa tersebut
     */
    #[Group('Sharing')]
    public function falsePositive(Request $request, Sharing $sharing)
    {
        Gate::authorize('falsePositive', $sharing);
        $request->validate(['reason' => ['string', 'max:255', 'required']]);
        $sharing->update([
            'priority' => 'rendah',
            'status' => ReportStatus::BUKAN_URGENT->value,
            'cutdown_for_report' => null
        ]);
        $sharing->nlp()->update([
            'status' => NlpAnalysisStatus::FALSE_POSITIVE->value,
            'reason' => $request->reason,
        ]);

        return $this->success(new SharingResource($sharing->load(['nlp', 'counseling'])));
    }

    /**
     * Get latest 2 sharing
     */
    #[Group('Notification')]
    #[ExcludeRouteFromDocs]
    public function latestOfStudent()
    {
        Gate::authorize('create', Sharing::class);
        $sharings = Sharing::whereUserId(Auth::id())->with(['nlp', 'counseling'])->latest()->take(2)->get();

        return $this->success(SharingResource::collection($sharings));
    }
}
