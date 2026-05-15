<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Sharing;
use App\Models\User;

class SharingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sharing $sharing): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role == UserRole::STUDENT->value;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sharing $sharing): bool
    {
        return $user->role == 'counselor' && $user->id == $sharing->user->counselor_id;
    }
}
