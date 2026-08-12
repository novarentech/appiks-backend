<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PsychologistSlot;
use App\Models\User;

class PsychologistSlotPolicy
{
    /**
     * Determine whether the user can manage psychologist slots.
     */
    public function manage(User $user): bool
    {
        return $user->role === UserRole::PSYCHOLOGIST->value && $user->psychologistProfile !== null;
    }

    /**
     * Determine whether the user can delete the specific slot.
     */
    public function delete(User $user, PsychologistSlot $psychologistSlot): bool
    {
        return $this->manage($user) && $psychologistSlot->psychologist_id === $user->psychologistProfile->id;
    }
}
