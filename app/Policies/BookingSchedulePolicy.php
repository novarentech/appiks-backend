<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BookingSchedule;
use App\Models\User;

class BookingSchedulePolicy
{
    /**
     * Psychologist can decide on a booking if the slot belongs to them.
     */
    public function decide(User $user, BookingSchedule $booking): bool
    {
        if ($user->role !== UserRole::PSYCHOLOGIST->value || !$user->psychologistProfile) {
            return false;
        }

        return $booking->slot && $booking->slot->psychologist_id === $user->psychologistProfile->id;
    }
}
