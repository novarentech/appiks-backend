<?php

namespace App\Actions\Psychologist;

use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use Illuminate\Database\Eloquent\Collection;

class GetPsychologistSlotsAction
{
    public function handle(PsychologistProfile $profile, ?string $start = null, ?string $end = null): Collection
    {
        $query = PsychologistSlot::where('psychologist_id', $profile->id);

        if ($start) {
            $query->whereDate('slot_date', '>=', $start);
        } else {
            $query->whereDate('slot_date', '>=', now()->toDateString());
        }

        if ($end) {
            $query->whereDate('slot_date', '<=', $end);
        }

        return $query->orderBy('slot_date')
            ->orderBy('slot_start_time')
            ->get();
    }
}
