<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\BookingSchedule;
use App\Models\Counseling;
use App\Models\PsychologistSlot;
use Carbon\Carbon;

class GetAvailableSlotsAction
{
    public function handle(Counseling $counseling, string $date): array
    {
        $profileId = $counseling->psychologist->psychologistProfile->id;
        $existingBook = $counseling->bookingSchedule->pluck('slot_id')->toArray();
        $slots = PsychologistSlot::where('psychologist_id', $profileId)
            ->whereDate('slot_date', $date)
            ->where('status', 'available')
            ->whereNotIn('id', $existingBook)
            ->get()
            ->map(function (PsychologistSlot $slot) {
                $start = Carbon::parse($slot->slot_start_time)->format('H:i');
                $end   = Carbon::parse($slot->slot_end_time)->format('H:i');

                return [
                    'slot_id'      => $slot->id,
                    'time_range'   => "{$start} - {$end} WIB",
                    'is_available' => true,
                ];
            });

        return [
            'selected_date'           => $date,
            'selected_date_formatted' => Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y'),
            'time_slots'              => $slots->values(),
        ];
    }
}
