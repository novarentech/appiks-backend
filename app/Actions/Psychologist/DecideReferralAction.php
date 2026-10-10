<?php

namespace App\Actions\Psychologist;

use App\Actions\CreateBookingScheduleAction;
use App\Enums\BookingStatus;
use App\Enums\CounselingStatus;
use App\Enums\SlotStatus;
use App\Jobs\GenerateGeminiReferralSummaryJob;
use App\Models\BookingSchedule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DecideReferralAction
{
    public function __construct(
        protected CreateBookingScheduleAction $createBookingAction
    ) {}

    public function handle(BookingSchedule $booking, array $data): BookingSchedule
    {
        $this->guardDecidable($booking, $data['action']);

        return DB::transaction(function () use ($booking, $data) {
            $slot = $booking->slot;
            $counseling = $booking->counseling;

            if ($data['action'] === 'confirm') {
                $booking->update(['status' => BookingStatus::CONFIRMED->value]);
                $slot->update(['status' => SlotStatus::CONFIRMED->value]);
                $counseling->update(['status' => CounselingStatus::DIJADWALKAN->value]);

                GenerateGeminiReferralSummaryJob::dispatch($counseling);
            } elseif ($data['action'] === 'reschedule') {
                // Jadwal digeser, bukan ditolak. Nilai `rescheduled` menjaga
                // filter "ditolak" tetap berisi penolakan sungguhan.
                $booking->update([
                    'status'        => BookingStatus::RESCHEDULED->value,
                    'reject_reason' => $data['reschedule_reason'],
                ]);

                // Slot lama dilepas agar bisa ditawarkan ke siswa lain.
                $slot->update(['status' => SlotStatus::AVAILABLE->value]);

                // Reschedule bersifat auto-setuju: booking pengganti langsung
                // terkonfirmasi, siswa cukup diinformasikan.
                $booking = $this->createBookingAction->handle(
                    [
                        'slot_id'       => $data['slot_id'],
                        'counseling_id' => $counseling->id,
                    ],
                    $counseling->student_id,
                    BookingStatus::CONFIRMED->value
                );

                // Sesi tetap akan berjalan, jadi psikolog tetap butuh ringkasan klinis.
                GenerateGeminiReferralSummaryJob::dispatch($counseling);
            } elseif ($data['action'] === 'reject') {
                $booking->update([
                    'status'        => BookingStatus::REJECTED->value,
                    'reject_reason' => $data['reschedule_reason'],
                ]);

                // Slot dilepas, dan siswa dikembalikan ke tahap memilih jadwal —
                // sama seperti ketika booking kadaluarsa. Rujukannya tetap hidup.
                $slot->update(['status' => SlotStatus::AVAILABLE->value]);

                if ($counseling->status->isActive()) {
                    $counseling->update(['status' => CounselingStatus::MENUNGGU_JADWAL->value]);
                }
            }

            return $booking->refresh()->load(['slot', 'student', 'counseling']);
        });
    }

    /**
     * Keputusan hanya boleh diambil atas booking yang memang sedang menunggu
     * respons psikolog, atau — untuk reschedule — yang sudah terkonfirmasi
     * tetapi sesinya belum ditutup.
     */
    private function guardDecidable(BookingSchedule $booking, string $action): void
    {
        if ($action === 'confirm' && $booking->status !== BookingStatus::PENDING) {
            throw new UnprocessableEntityHttpException('Booking ini tidak lagi berstatus pending.');
        }

        if ($action === 'reschedule' && ! in_array($booking->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED], true)) {
            throw new UnprocessableEntityHttpException('Booking ini tidak dapat dijadwalkan ulang.');
        }

        if ($action === 'reject' && $booking->status !== BookingStatus::PENDING) {
            throw new UnprocessableEntityHttpException('Booking ini tidak lagi berstatus pending.');
        }
    }
}
