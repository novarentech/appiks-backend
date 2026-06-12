<?php

namespace App\Actions\Psychologist;

use App\Models\User;

class TogglePsychologistStatusAction
{
    /**
     * Toggle the active status of a psychologist profile.
     */
    public function handle(User $user): User
    {
        $profile = $user->psychologistProfile;
        if ($profile) {
            $profile->update([
                'is_active' => !$profile->is_active
            ]);
        }

        return $user->load('psychologistProfile');
    }
}
