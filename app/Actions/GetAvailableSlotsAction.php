<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Counseling;
use App\Models\PsychologistSlot;
use Carbon\Carbon;

class GetAvailableSlotsAction
{
    public function handle(Counseling $counseling, string $date): array
    {
        $profileId = $counseling->psychologist->psychologistProfile->id;

        // Batas tanggal harus sama dengan GetAvailableDatesAction, kalau tidak
        // jumlah slot yang dijanjikan endpoint tanggal bisa berbeda dari yang
        // benar-benar dikembalikan di sini.
        $minDate = now()->addDays(2)->toDateString();

        $slots = PsychologistSlot::where('psychologist_id', $profileId)
            ->whereDate('slot_date', $date)
            ->whereDate('slot_date', '>=', $minDate)
            ->where('status', SlotStatus::AVAILABLE->value)
            // Hanya slot dengan booking yang masih hidup yang dikecualikan.
            // Slot yang booking-nya expired/rejected/rescheduled boleh dipilih
            // ulang, termasuk oleh rujukan yang sama.
            ->whereDoesntHave('bookingSchedule', function ($q) {
                $q->whereIn('status', BookingStatus::holdsSlotValues());
            })
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
