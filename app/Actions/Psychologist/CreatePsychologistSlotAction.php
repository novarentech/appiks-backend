<?php

namespace App\Actions\Psychologist;

use App\Enums\SlotStatus;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CreatePsychologistSlotAction
{
    /**
     * @return Collection<int, PsychologistSlot>
     */
    public function handle(array $data, PsychologistProfile $profile): Collection
    {
        // 1. Time logic validation (start must be before end)
        if (strtotime($data['slot_start_time']) >= strtotime($data['slot_end_time'])) {
            throw new UnprocessableEntityHttpException('Waktu mulai harus sebelum waktu selesai.');
        }

        // 2. Overlap helper
        $startTime = date('H:i:s', strtotime($data['slot_start_time']));
        $endTime   = date('H:i:s', strtotime($data['slot_end_time']));
        $baseDate  = Carbon::parse($data['slot_date']);

        $hasOverlap = function (string $date) use ($profile, $startTime, $endTime): bool {
            return PsychologistSlot::where('psychologist_id', $profile->id)
                ->whereDate('slot_date', $date)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('slot_start_time', '<', $endTime)
                      ->where('slot_end_time', '>', $startTime);
                })->exists();
        };

        // 3. Create slot(s) - skip if overlapped, do not fail
        return DB::transaction(function () use ($data, $profile, $baseDate, $hasOverlap) {
            $createdSlots = collect();

            if (! $hasOverlap($baseDate->toDateString())) {
                $createdSlots->push(PsychologistSlot::create([
                    'psychologist_id' => $profile->id,
                    'slot_date'       => $baseDate->toDateString(),
                    'slot_start_time' => $data['slot_start_time'],
                    'slot_end_time'   => $data['slot_end_time'],
                    'status'          => SlotStatus::AVAILABLE->value,
                ]));
            }

            $isRepeat = filter_var($data['repeat'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($isRepeat) {
                $current = $baseDate->copy()->addWeek();
                $until   = $baseDate->copy()->addYear();

                while ($current->lessThanOrEqualTo($until)) {
                    $dateStr = $current->toDateString();

                    if (! $hasOverlap($dateStr)) {
                        $createdSlots->push(PsychologistSlot::create([
                            'psychologist_id' => $profile->id,
                            'slot_date'       => $dateStr,
                            'slot_start_time' => $data['slot_start_time'],
                            'slot_end_time'   => $data['slot_end_time'],
                            'status'          => SlotStatus::AVAILABLE->value,
                        ]));
                    }

                    $current->addWeek();
                }
            }

            return $createdSlots;
        });
    }
}
