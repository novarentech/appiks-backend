<?php

namespace App\Actions\Psychologist;

use App\Enums\BookingStatus;
use App\Models\BookingSchedule;
use App\Models\PsychologistProfile;
use Illuminate\Database\Eloquent\Collection;

class GetPendingReferralsAction
{
    public function handle(PsychologistProfile $profile): Collection
    {
        return BookingSchedule::with([
                'student',
                'counseling.counselor',
                'counseling.logs',
                'slot'
            ])
            ->where('status', BookingStatus::PENDING->value)
            ->whereHas('slot', function ($query) use ($profile) {
                $query->where('psychologist_id', $profile->id);
            })
            ->orderBy('deadline_at', 'asc')
            ->get();
    }
}
