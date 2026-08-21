<?php

namespace App\Http\Controllers;

use App\Actions\Psychologist\DecideReferralAction;
use App\Actions\Psychologist\GetPendingReferralsAction;
use App\Enums\BookingStatus;
use App\Enums\CounselingStatus;
use App\Http\Requests\DecideReferralRequest;
use App\Http\Resources\BookingScheduleResource;
use App\Models\BookingSchedule;
use App\Models\Counseling;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PsychologistReferralController extends Controller
{
    use ApiResponder;

    /**
     * Get psychologist referrals overview counts
     *
     * Mendapatkan jumlah rujukan masuk berdasarkan status kondisi (pending, confirmed, selesai).
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     pending: int,
     *     confirmed: int,
     *     selesai: int
     *   }
     * }
     */
    #[Group('Psychologist')]
    public function overview(): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;

        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        $baseQuery = BookingSchedule::whereHas('slot', function ($query) use ($profile) {
            $query->where('psychologist_id', $profile->id);
        });

        $pending = (clone $baseQuery)
            ->where('status', BookingStatus::PENDING->value)
            ->count();

        $confirmed = (clone $baseQuery)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereHas('counseling', function ($query) {
                $query->where('status', '!=', CounselingStatus::SELESAI->value);
            })
            ->count();

        $selesai = (clone $baseQuery)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereHas('counseling', function ($query) {
                $query->where('status', CounselingStatus::SELESAI->value);
            })
            ->count();

        return $this->success([
            'pending'   => $pending,
            'confirmed' => $confirmed,
            'selesai'   => $selesai,
        ], 'Ringkasan rujukan berhasil diambil.');
    }

    /**
     * Get all referrals for authenticated psychologist
     *
     * Mendapatkan seluruh daftar rujukan yang masuk untuk psikolog yang terautentikasi dengan paginasi dan filter.
     *
     * @queryParam status string Filter status rujukan ('menunggu konfirmasi', 'terkonfirmasi', 'selesai', 'ditolak', 'kadaluarsa'). Example: menunggu konfirmasi
     * @queryParam priority string Filter prioritas rujukan ('kritis', 'prioritas'). Example: kritis
     * @queryParam batas_waktu string Filter batas waktu rujukan ('aktif', 'kadaluarsa'). Example: aktif
     * @queryParam per_page int Jumlah data per halaman. Default: 10. Example: 10
     * @queryParam page int Halaman yang ingin ditampilkan. Example: 1
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array<array{
     *     id: int,
     *     counseling_id: int,
     *     slot_id: int,
     *     student_id: int,
     *     status: string,
     *     deadline_at: string,
     *     location: string|null,
     *     reject_reason: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     student: array{
     *       id: int,
     *       name: string,
     *       username: string,
     *       identifier: string|null
     *     },
     *     counseling: array{
     *       id: int,
     *       status: string,
     *       type: string,
     *       resolution: string|null,
     *       counselor: array{
     *         id: int,
     *         name: string
     *       }
     *     },
     *     slot: array{
     *       id: int,
     *       slot_date: string,
     *       slot_start_time: string,
     *       slot_end_time: string,
     *       status: string
     *     }
     *   }>,
     *   links: array{
     *     first: string|null,
     *     last: string|null,
     *     prev: string|null,
     *     next: string|null
     *   },
     *   meta: array{
     *     current_page: int,
     *     from: int|null,
     *     last_page: int,
     *     path: string,
     *     per_page: int,
     *     to: int|null,
     *     total: int
     *   }
     * }
     */
    #[Group('Psychologist')]
    public function index(Request $request): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;

        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        $query = BookingSchedule::with([
            'student',
            'counseling.counselor',
            'counseling.sharing',
            'counseling.logs',
            'slot',
        ])
        ->whereHas('slot', function ($q) use ($profile) {
            $q->where('psychologist_id', $profile->id);
        });

        // Filter: status
        if ($request->filled('status')) {
            $status = strtolower(trim($request->input('status')));
            if (in_array($status, ['menunggu konfirmasi', 'menunggu_konfirmasi', 'pending'])) {
                $query->where('status', BookingStatus::PENDING->value);
            } elseif (in_array($status, ['terkonfirmasi', 'confirmed'])) {
                $query->where('status', BookingStatus::CONFIRMED->value)
                    ->whereHas('counseling', function ($q) {
                        $q->where('status', '!=', CounselingStatus::SELESAI->value);
                    });
            } elseif ($status === 'selesai') {
                $query->where('status', BookingStatus::CONFIRMED->value)
                    ->whereHas('counseling', function ($q) {
                        $q->where('status', CounselingStatus::SELESAI->value);
                    });
            } elseif (in_array($status, ['ditolak', 'rejected'])) {
                $query->where('status', BookingStatus::REJECTED->value);
            } elseif (in_array($status, ['kadaluarsa', 'expired'])) {
                $query->where(function ($q) {
                    $q->where('status', BookingStatus::EXPIRED->value)
                        ->orWhere(function ($sq) {
                            $sq->where('status', BookingStatus::PENDING->value)
                                ->where('deadline_at', '<=', now());
                        });
                });
            }
        }

        // Filter: priority
        if ($request->filled('priority')) {
            $priority = strtolower(trim($request->input('priority')));
            $priorityValue = in_array($priority, ['kritis', 'tinggi']) ? 'tinggi' : 'rendah';

            $sharingIds = \App\Models\Sharing::where('priority', $priorityValue)->pluck('id');
            $counselingIds = Counseling::whereIn('sharing_id', $sharingIds)->pluck('id');

            $query->whereIn('counseling_id', $counselingIds);
        }

        // Filter: batas_waktu
        if ($request->filled('batas_waktu')) {
            $batasWaktu = strtolower(trim($request->input('batas_waktu')));
            if ($batasWaktu === 'aktif') {
                $query->where(function ($q) {
                    $q->where('deadline_at', '>', now())
                        ->orWhere('status', BookingStatus::CONFIRMED->value);
                });
            } elseif ($batasWaktu === 'kadaluarsa') {
                $query->where(function ($q) {
                    $q->where('deadline_at', '<=', now())
                        ->orWhere('status', BookingStatus::EXPIRED->value);
                });
            }
        }

        $query->orderByDesc('created_at');

        $perPage = (int) $request->input('per_page', $request->input('limit', 10));
        $referrals = $query->paginate($perPage);

        return $this->success(BookingScheduleResource::collection($referrals)->response()->getData(true), 'Daftar semua rujukan berhasil diambil.');
    }

    /**
     * Get pending incoming referrals.
     *
     * List all pending student booking schedules for the authenticated psychologist.
     */
    #[Group('Psychologist')]
    public function pending(GetPendingReferralsAction $action): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;
        
        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        $referrals = $action->handle($profile);

        return $this->success(BookingScheduleResource::collection($referrals), 'Daftar rujukan masuk berhasil diambil.');
    }

    /**
     * Decide on an incoming referral schedule.
     *
     * Confirm or reject a pending student booking schedule referral.
     */
    #[Group('Psychologist')]
    public function decide(DecideReferralRequest $request, BookingSchedule $booking, DecideReferralAction $action): JsonResponse
    {
        Gate::authorize('decide', $booking);

        $result = $action->handle($booking, $request->validated());

        $message = $request->action === 'confirm' 
            ? 'Rujukan berhasil dikonfirmasi.' 
            : 'Rujukan telah ditolak.';

        return $this->success(new BookingScheduleResource($result), $message);
    }
}
