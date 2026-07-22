<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingScheduleCreated;
use App\Models\BookingSchedule;
use App\Models\PsychologistSlot;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateBookingScheduleAction
{
    public function handle(array $data, int $studentId): BookingSchedule
    {
        return DB::transaction(function () use ($data, $studentId) {
            // Pessimistic lock: prevent two concurrent requests from booking the same slot
            $slot = PsychologistSlot::lockForUpdate()->findOrFail($data['slot_id']);

            if ($slot->status !== SlotStatus::AVAILABLE) {
                throw new ConflictHttpException(
                    'Slot ini sudah diambil siswa lain. Silakan pilih slot lain.'
                );
            }

            // Mark slot as tentative
            $slot->update(['status' => SlotStatus::TENTATIVE->value]);

            // Create the booking record
            $booking = BookingSchedule::create([
                'counseling_id' => $data['counseling_id'],
                'slot_id'       => $slot->id,
                'student_id'    => $studentId,
                'status'        => BookingStatus::PENDING->value,
                'deadline_at'   => now()->addHours(24),
            ]);

            // Dispatch event — listeners attached in future tickets
            BookingScheduleCreated::dispatch($booking);

            return $booking->load('slot');
        });
    }
}
