<?php

namespace App\Actions\Psychologist;

use App\Enums\SlotStatus;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CreatePsychologistSlotAction
{
    public function handle(array $data, PsychologistProfile $profile): PsychologistSlot
    {
        // 1. Time logic validation (start must be before end)
        if (strtotime($data['slot_start_time']) >= strtotime($data['slot_end_time'])) {
            throw new UnprocessableEntityHttpException('Waktu mulai harus sebelum waktu selesai.');
        }

        // 2. Overlap validation
        $startTime = date('H:i:s', strtotime($data['slot_start_time']));
        $endTime   = date('H:i:s', strtotime($data['slot_end_time']));

        $overlap = PsychologistSlot::where('psychologist_id', $profile->id)
            ->whereDate('slot_date', $data['slot_date'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('slot_start_time', '<', $endTime)
                  ->where('slot_end_time', '>', $startTime);
            })->exists();

        if ($overlap) {
            throw new ConflictHttpException('Slot waktu tumpang tindih dengan jadwal yang sudah ada.');
        }

        // 3. Create slot
        return PsychologistSlot::create([
            'psychologist_id' => $profile->id,
            'slot_date'       => $data['slot_date'],
            'slot_start_time' => $data['slot_start_time'],
            'slot_end_time'   => $data['slot_end_time'],
            'status'          => SlotStatus::AVAILABLE->value,
        ]);
    }
}
