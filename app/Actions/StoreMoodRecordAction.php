<?php

namespace App\Actions;

use App\Data\MoodResponses;
use App\Enums\MoodStatus;
use App\Models\MoodRecord;

class StoreMoodRecordAction
{
    public function handle(array $validated): array
    {
        MoodRecord::create($validated);

        $moodStatus = MoodStatus::from($validated['status']);
        $statusLabel = $moodStatus->label(); // 'Aman' atau 'Tidak Aman'
        $pesan = MoodResponses::get($statusLabel);

        return ['status' => $statusLabel, 'pesan' => $pesan];
    }
}
