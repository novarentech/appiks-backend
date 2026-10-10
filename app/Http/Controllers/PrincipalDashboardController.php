<?php

namespace App\Http\Controllers;

use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Sharing;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrincipalDashboardController extends Controller
{
    use ApiResponder;

    private function ensurePrincipal(): void
    {
        if (auth()->user()->role !== UserRole::HEADTEACHER->value) {
            abort(403, 'Akses ditolak. Hanya Kepala Sekolah yang diizinkan.');
        }
    }

    /**
     * Get principal dashboard statistics
     *
     * Mendapatkan ringkasan statistik dasbor kepala sekolah (kasus aktif dan intervensi yang diselesaikan).
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     need_intention: int,
     *     resolved_interventions: int
     *   }
     * }
     */
    #[Group('Principal Dashboard')]
    public function stats(): JsonResponse
    {
        $this->ensurePrincipal();
        $schoolId = auth()->user()->school_id;

        $stats = [
            'need_intention' => Sharing::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
                ->where('status', ReportStatus::MENUNGGU_TINJAUAN->value)
                ->count(),
            'resolved_interventions' => Sharing::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
                ->where('status', ReportStatus::DITINJAU->value)
                ->count(),
        ];

        return $this->success($stats, 'Statistik dasbor berhasil diambil.');
    }

    /**
     * Get red zone incidents list
     *
     * Mendapatkan daftar insiden zona merah sekolah dengan sensor data rahasia dan status breach SLA.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array<array{
     *     id: int,
     *     name: string,
     *     room: string|null,
     *     status: string,
     *     priority: string,
     *     created_at: string,
     *     scheduled_at: string|null,
     *     acknowledged_at: string|null,
     *     assigned_counselor: string|null,
     *     is_sla_breached: bool
     *   }>
     * }
     */
    #[Group('Principal Dashboard')]
    public function incidents(Request $request): JsonResponse
    {
        $this->ensurePrincipal();
        $schoolId = auth()->user()->school_id;

        // STRICT RBAC: Select only metadata columns, omit description/reply/title
        $query = Sharing::select(['id', 'user_id', 'status', 'priority', 'created_at', 'acknowledged_at'])
            ->with(['counseling:id,sharing_id,psychologist_id,scheduled_at', 'user:id,counselor_id,room_id,name', 'user.counselor:id,name', 'user.room:id,level,name', 'counseling.psychologist:id,name'])
            ->whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->where('priority', 'tinggi') // Only fetch high priority (Red Zone)
            ->orderByDesc('created_at');

        // Filter: student name
        if ($request->filled('name')) {
            $name = trim($request->input('name'));
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$name}%"));
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

        // Filter: is_sla_breached
        if ($request->has('is_breached') && $request->input('is_breached') !== null && $request->input('is_breached') !== '') {
            $isBreached = filter_var($request->input('is_breached'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isBreached === true) {
                $query->whereNull('acknowledged_at')
                    ->where('created_at', '<=', now()->subHours(2));
            } elseif ($isBreached === false) {
                $query->where(function ($q) {
                    $q->whereNotNull('acknowledged_at')
                        ->orWhere('created_at', '>', now()->subHours(2));
                });
            }
        }

        // Filter: counselor name
        $counselorName = $request->input('counselor_name', $request->input('counselor', $request->input('assigned_counselor')));
        if (!empty($counselorName)) {
            $counselorName = trim($counselorName);
            $query->whereHas('user.counselor', fn($q) => $q->where('name', 'like', "%{$counselorName}%"));
        }

        if ($request->has('page')) {
            $paginated = $query->paginate($request->input('per_page', 15));
            $paginated->getCollection()->transform(function ($incident) {
                $isBreached = is_null($incident->acknowledged_at) && $incident->created_at->diffInHours(now()) >= 2;

                return [
                    'id' => $incident->id,
                    'name' => $incident->user->name,
                    'room' => $incident->user->room ? ($incident->user->room->level.' '.$incident->user->room->name) : null,
                    'status' => $incident->status,
                    'priority' => $incident->priority,
                    'created_at' => $incident->created_at,
                    'scheduled_at' => $incident->counseling?->scheduled_at,
                    'acknowledged_at' => $incident->acknowledged_at,
                    'assigned_counselor' => $incident->user->counselor->name ?? null,
                    'is_sla_breached' => $isBreached,
                ];
            });

            return $this->success($paginated, 'Daftar insiden berhasil diambil.');
        }

        $incidents = $query->get();

        $mapped = $incidents->map(function ($incident) {
            // SLA Logic (BE-12.3): Breached if not acknowledged for 2 hours
            $isBreached = is_null($incident->acknowledged_at) && $incident->created_at->diffInHours(now()) >= 2;

            return [
                'id' => $incident->id,
                'name' => $incident->user->name,
                'room' => $incident->user->room ? ($incident->user->room->level.' '.$incident->user->room->name) : null,
                'status' => $incident->status,
                'priority' => $incident->priority,
                'created_at' => $incident->created_at,
                'scheduled_at' => $incident->counseling?->scheduled_at,
                'acknowledged_at' => $incident->acknowledged_at,
                'assigned_counselor' => $incident->user->counselor->name ?? null,
                'is_sla_breached' => $isBreached,
            ];
        });

        return $this->success($mapped, 'Daftar insiden berhasil diambil.');
    }

    /**
     * Mark notification as read
     *
     * Menandai notifikasi kepala sekolah sebagai telah dibaca.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: null
     * }
     */
    #[Group('Principal Dashboard')]
    public function markNotificationRead($id): JsonResponse
    {
        $this->ensurePrincipal();
        
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead(); // Sets read_at to now()

        return $this->success(null, 'Notifikasi berhasil ditandai telah dibaca.');
    }
}
