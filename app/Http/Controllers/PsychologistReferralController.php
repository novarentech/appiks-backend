<?php

namespace App\Http\Controllers;

use App\Actions\Psychologist\DecideReferralAction;
use App\Actions\Psychologist\GetPendingReferralsAction;
use App\Enums\BookingStatus;
use App\Http\Requests\DecideReferralRequest;
use App\Http\Requests\GetPsychologistReferralsRequest;
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
     * Mendapatkan jumlah rujukan masuk per status booking. Tiga kunci teratas
     * dipertahankan untuk kompatibilitas; `by_status` memuat seluruh nilai enum
     * dengan zero-fill sehingga tidak ada status yang luput dari hitungan.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     pending: int,
     *     confirmed: int,
     *     selesai: int,
     *     by_status: array{pending: int, confirmed: int, rescheduled: int, rejected: int, expired: int, finished: int},
     *     total: int
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

        // Hitung per status dengan zero-fill dari enum, supaya tidak ada nilai
        // yang luput dari hitungan saat enum bertambah.
        $counts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = [];
        foreach (BookingStatus::cases() as $case) {
            $byStatus[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $this->success([
            // Tiga kunci lama dipertahankan agar frontend yang sudah ada tidak rusak.
            'pending'   => $byStatus[BookingStatus::PENDING->value],
            'confirmed' => $byStatus[BookingStatus::CONFIRMED->value],
            'selesai'   => $byStatus[BookingStatus::FINISHED->value],
            'by_status' => $byStatus,
            'total'     => array_sum($byStatus),
        ], 'Ringkasan rujukan berhasil diambil.');
    }

    /**
     * Get all referrals for authenticated psychologist
     *
     * Mendapatkan seluruh daftar rujukan yang masuk untuk psikolog yang terautentikasi dengan paginasi dan filter.
     * @queryParam search string Filter pencarian berdasarkan nama siswa. Example: Budi
     * @queryParam status 'menunggu konfirmasi'|'terkonfirmasi'|'selesai'|'ditolak'|'kadaluarsa' Filter status rujukan. Example: menunggu konfirmasi
     * @queryParam priority 'kritis'|'prioritas' Filter prioritas rujukan. Example: kritis
     * @queryParam batas_waktu 'aktif'|'kadaluarsa' Filter batas waktu rujukan. Example: aktif
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
    public function index(GetPsychologistReferralsRequest $request): JsonResponse
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

        // Filter: search (student name)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter: status
        if ($request->filled('status')) {
            $status = strtolower(trim($request->input('status')));
            if (in_array($status, ['menunggu konfirmasi', 'menunggu_konfirmasi', 'pending'])) {
                $query->where('status', BookingStatus::PENDING->value);
            } elseif (in_array($status, ['terkonfirmasi', 'confirmed'])) {
                $query->where('status', BookingStatus::CONFIRMED->value);
            } elseif (in_array($status, ['selesai', 'finished'])) {
                // Sesi yang sudah ditutup psikolog lewat endpoint feedback.
                $query->where('status', BookingStatus::FINISHED->value);
            } elseif (in_array($status, ['ditolak', 'rejected'])) {
                $query->where('status', BookingStatus::REJECTED->value);
            } elseif (in_array($status, ['dijadwal ulang', 'dijadwal_ulang', 'rescheduled'])) {
                $query->where('status', BookingStatus::RESCHEDULED->value);
            } elseif (in_array($status, ['kadaluarsa', 'expired'])) {
                $query->where(function ($q) {
                    $q->where('status', BookingStatus::EXPIRED->value)
                        ->orWhere(function ($sq) {
                            $sq->where('status', BookingStatus::PENDING->value)
                                ->where('deadline_at', '<=', now());
                        });
                });
            } else {
                // Status tidak dikenal: kembalikan kosong, jangan diam-diam
                // mengembalikan seluruh daftar.
                $query->whereRaw('1 = 0');
            }
        }

        // Filter: priority
        if ($request->filled('priority')) {
            $priority = strtolower(trim($request->input('priority')));
            $priorityValue = $priority == 'kritis' ? ['tinggi'] : ['rendah','sedang'];

            $sharingIds = \App\Models\Sharing::whereIn('priority', $priorityValue)->pluck('id');
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
     * `confirm` menerima jadwal yang diajukan siswa, `reschedule` menggesernya ke
     * slot lain (bersifat auto-setuju — siswa hanya diinformasikan), dan `reject`
     * menolak rujukannya sehingga siswa kembali ke tahap memilih jadwal.
     */
    #[Group('Psychologist')]
    public function decide(DecideReferralRequest $request, BookingSchedule $booking, DecideReferralAction $action): JsonResponse
    {
        Gate::authorize('decide', $booking);

        $result = $action->handle($booking, $request->validated());

        $message = match ($request->action) {
            'confirm'    => 'Rujukan berhasil dikonfirmasi.',
            'reschedule' => 'Rujukan telah dijadwalkan ulang.',
            'reject'     => 'Rujukan telah ditolak.',
            default      => 'Keputusan rujukan tersimpan.',
        };

        return $this->success(new BookingScheduleResource($result), $message);
    }
}
