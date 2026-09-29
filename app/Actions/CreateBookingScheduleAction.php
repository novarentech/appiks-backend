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
    public function handle(array $data, int $studentId, string $status = BookingStatus::PENDING->value): BookingSchedule
    {
        return DB::transaction(function () use ($data, $studentId, $status) {
            // Pessimistic lock: prevent two concurrent requests from booking the same slot
            $slot = PsychologistSlot::lockForUpdate()->findOrFail($data['slot_id']);

            // Create the booking record
            $booking = BookingSchedule::create([
                'counseling_id' => $data['counseling_id'],
                'slot_id'       => $slot->id,
                'student_id'    => $studentId,
                'status'        => $status,
                'deadline_at'   => $status === BookingStatus::PENDING->value ? now()->addHours(24) : now()->subDays(2),
            ]);

            // Dispatch event — listeners attached in future tickets
            BookingScheduleCreated::dispatch($booking);

            return $booking->load('slot');
        });
    }
}
