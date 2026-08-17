<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Sharing;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

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
     *     active_cases: int,
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
            'active_cases' => Sharing::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
                ->where('priority', 'tinggi')
                ->where('status', '!=', ReportStatus::SELESAI->value)
                ->count(),
            'resolved_interventions' => Sharing::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
                ->where('status', ReportStatus::SELESAI->value)
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
     *     status: string,
     *     priority: string,
     *     created_at: string,
     *     acknowledged_at: string|null,
     *     assigned_counselor: string,
     *     is_sla_breached: bool
     *   }>
     * }
     */
    #[Group('Principal Dashboard')]
    public function incidents(): JsonResponse
    {
        $this->ensurePrincipal();
        $schoolId = auth()->user()->school_id;

        // STRICT RBAC: Select only metadata columns, omit description/reply/title
        $incidents = Sharing::select(['id', 'user_id', 'status', 'priority', 'created_at', 'acknowledged_at'])
            ->with(['user:id,counselor_id', 'user.counselor:id,name'])
            ->whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->where('priority', 'tinggi') // Only fetch high priority (Red Zone)
            ->orderByDesc('created_at')
            ->get();

        $mapped = $incidents->map(function ($incident) {
            // SLA Logic (BE-12.3): Breached if not acknowledged for 24 hours
            $isBreached = is_null($incident->acknowledged_at) && $incident->created_at->diffInHours(now()) >= 24;

            return [
                'id' => $incident->id,
                'status' => $incident->status,
                'priority' => $incident->priority,
                'created_at' => $incident->created_at,
                'acknowledged_at' => $incident->acknowledged_at,
                'assigned_counselor' => $incident->user->counselor->name ?? 'Belum Ditugaskan',
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
