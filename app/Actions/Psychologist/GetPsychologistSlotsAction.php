<?php

namespace App\Actions\Psychologist;

use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use Illuminate\Database\Eloquent\Collection;

class GetPsychologistSlotsAction
{
    public function handle(PsychologistProfile $profile): Collection
    {
        return PsychologistSlot::where('psychologist_id', $profile->id)
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->orderBy('slot_date')
            ->orderBy('slot_start_time')
            ->get();
    }
}
