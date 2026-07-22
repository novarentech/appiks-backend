<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Counseling;
use App\Models\PsychologistSlot;
use Carbon\Carbon;

class GetAvailableDatesAction
{
    public function handle(Counseling $counseling): array
    {
        $profileId = $counseling->psychologist->psychologistProfile->id;
        $minDate = now()->addDays(2)->toDateString();

        $slots = PsychologistSlot::where('psychologist_id', $profileId)
            ->where('slot_date', '>=', $minDate)
            ->where('status', 'available')
            ->whereDoesntHave('bookingSchedule', function ($q) {
                $q->whereIn('status', [
                    BookingStatus::PENDING->value,
                    BookingStatus::CONFIRMED->value,
                ]);
            })
            ->get()
            ->groupBy(fn ($slot) => $slot->slot_date->toDateString());

        $availableDates = $slots->map(function ($daySlots, $date) {
            $count  = $daySlots->count();
            $carbon = Carbon::parse($date)->locale('id');
            return [
                'date_raw'              => $date,
                'date_formatted'        => $carbon->translatedFormat('l, j F Y'),
                'available_slots_count' => $count,
                'slot_label'            => "{$count} slot tersedia",
                'is_selectable'         => $count > 0,
            ];
        })->values();

        return [
            'earliest_available_date' => $slots->keys()->first(),
            'available_dates'         => $availableDates,
        ];
    }
}
