<?php

namespace App\Actions\Psychologist;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Models\BookingSchedule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DecideReferralAction
{
    public function handle(BookingSchedule $booking, array $data): BookingSchedule
    {
        if ($booking->status !== BookingStatus::PENDING) {
            throw new UnprocessableEntityHttpException('Booking ini tidak lagi berstatus pending.');
        }

        return DB::transaction(function () use ($booking, $data) {
            $slot = $booking->slot;

            if ($data['action'] === 'confirm') {
                $booking->update(['status' => BookingStatus::CONFIRMED->value]);
                $slot->update(['status' => SlotStatus::CONFIRMED->value]);

                BookingConfirmed::dispatch($booking);
            } elseif ($data['action'] === 'reject') {
                $booking->update([
                    'status' => BookingStatus::REJECTED->value,
                    'reject_reason' => $data['reject_reason']
                ]);
                $slot->update(['status' => SlotStatus::AVAILABLE->value]);

                BookingRejected::dispatch($booking);
            }

            return $booking->refresh()->load(['slot', 'student', 'counseling']);
        });
    }
}
