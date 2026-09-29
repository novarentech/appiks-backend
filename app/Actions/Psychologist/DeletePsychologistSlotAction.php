<?php

namespace App\Actions\Psychologist;

use App\Models\PsychologistSlot;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeletePsychologistSlotAction
{
    public function handle(PsychologistSlot $slot, bool $repeat = false): int
    {
        $baseDate = Carbon::parse($slot->slot_date);

        // Jika repeat false, validasi slot tunggal dan hapus
        if (! $repeat) {
            if ($baseDate->lessThan(now()->startOfDay())) {
                throw new UnprocessableEntityHttpException('Hanya slot setelah hari ini yang dapat dihapus.');
            }

            if ($slot->bookingSchedule()->exists()) {
                throw new UnprocessableEntityHttpException('Slot tidak dapat dihapus karena sudah memiliki jadwal booking.');
            }

            $slot->delete();
            return 1;
        }

        // 4. Jika repeat true, hapus slot berulang mingguan hingga 1 tahun yang belum dibooking
        $dates = [];
        $current = $baseDate->copy();
        $until   = $baseDate->copy()->addYear();

        while ($current->lessThanOrEqualTo($until)) {
            $dates[] = $current->toDateString();
            $current->addWeek();
        }

        $startTime = $slot->getRawOriginal('slot_start_time') ?: Carbon::parse($slot->slot_start_time)->format('H:i:s');
        $endTime   = $slot->getRawOriginal('slot_end_time') ?: Carbon::parse($slot->slot_end_time)->format('H:i:s');

        return PsychologistSlot::where('psychologist_id', $slot->psychologist_id)
            ->whereIn('slot_date', $dates)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->where('slot_start_time', $startTime)
            ->where('slot_end_time', $endTime)
            ->doesntHave('bookingSchedule')
            ->delete();
    }
}
