<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\CounselingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingScheduleCreated;
use App\Models\BookingSchedule;
use App\Models\Counseling;
use App\Models\PsychologistSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateBookingScheduleAction
{
    public function handle(array $data, int $studentId, string $status = BookingStatus::PENDING->value): BookingSchedule
    {
        return DB::transaction(function () use ($data, $studentId, $status) {
            // Pessimistic lock: prevent two concurrent requests from booking the same slot
            $slot = PsychologistSlot::lockForUpdate()->findOrFail($data['slot_id']);
            $counseling = Counseling::findOrFail($data['counseling_id']);

            $this->guardAgainstDoubleBooking($counseling);

            // Create the booking record
            $booking = BookingSchedule::create([
                'counseling_id' => $counseling->id,
                'slot_id'       => $slot->id,
                'student_id'    => $studentId,
                'status'        => $status,
                'deadline_at'   => $this->resolveDeadline($status, $slot),
            ]);

            $this->syncSlotAndCounseling($status, $slot, $counseling);

            // Dispatch event — listeners attached in future tickets
            BookingScheduleCreated::dispatch($booking);

            return $booking->load('slot');
        });
    }

    /**
     * Sebuah rujukan hanya boleh punya satu booking hidup. Booking yang sudah
     * expired, rejected, atau rescheduled melepas slotnya, sehingga siswa boleh
     * mengajukan jadwal baru pada rujukan yang sama.
     */
    private function guardAgainstDoubleBooking(Counseling $counseling): void
    {
        $hasLiveBooking = $counseling->bookingSchedule()
            ->whereIn('status', BookingStatus::holdsSlotValues())
            ->exists();

        if ($hasLiveBooking) {
            throw new ConflictHttpException('Rujukan ini sudah memiliki jadwal yang masih aktif.');
        }
    }

    /**
     * `deadline_at` adalah batas waktu psikolog merespons, jadi hanya booking
     * berstatus pending yang punya tenggat 24 jam. Booking yang langsung
     * dibuat terkonfirmasi (jalur reschedule oleh psikolog) memakai waktu sesi
     * sebagai tenggat, supaya tidak terbaca sebagai sudah kadaluarsa.
     */
    private function resolveDeadline(string $status, PsychologistSlot $slot): Carbon
    {
        if ($status === BookingStatus::PENDING->value) {
            return now()->addHours(24);
        }

        return Carbon::parse(
            $slot->slot_date->format('Y-m-d').' '.$slot->slot_start_time->format('H:i')
        );
    }

    private function syncSlotAndCounseling(string $status, PsychologistSlot $slot, Counseling $counseling): void
    {
        if ($status === BookingStatus::PENDING->value) {
            // Slot ditahan sementara sampai psikolog memutuskan.
            $slot->update(['status' => SlotStatus::TENTATIVE->value]);
            $counseling->update(['status' => CounselingStatus::MENUNGGU_KONFIRMASI->value]);

            return;
        }

        if ($status === BookingStatus::CONFIRMED->value) {
            $slot->update(['status' => SlotStatus::CONFIRMED->value]);
            $counseling->update(['status' => CounselingStatus::DIJADWALKAN->value]);
        }
    }
}
