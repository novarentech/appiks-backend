<?php

namespace App\Http\Controllers;

use App\Enums\NlpAnalysisStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Requests\CreateSharingRequest;
use App\Http\Requests\ReplySharingRequest;
use App\Http\Resources\SharingResource;
use App\Models\Sharing;
use App\Models\User;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
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
    public function index()
    {
        $user = Auth::user();
        if ($user->role == UserRole::STUDENT->value) {
            $sharings = $user->sharing()->with(['user', 'nlp'])->orderBy('replied_at')->get();
        } elseif ($user->role == UserRole::COUNSELOR->value) {
            $sharings = Sharing::with(['user', 'user.room', 'nlp'])->whereIn('user_id', $user->counselored->pluck('id'))->get();
        } else {
            $sharings = collect();
        }

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
        $sharing->update(['status' => ReportStatus::DITINJAU->value]);

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
        $request->validate(['reason'=>['string','max:255','required']]);
        $sharing->update([
            'priority' => 'rendah',
            'status' => ReportStatus::SELESAI->value,
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
