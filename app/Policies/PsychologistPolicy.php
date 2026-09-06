<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class PsychologistPolicy
{
    /**
     * Determine whether the user can view any psychologists.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can view the psychologist.
     */
    public function view(User $user, User $psychologist): bool
    {
        return $user->role === UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can create psychologists.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can update the psychologist.
     */
    public function update(User $user): bool
    {
        return $user->role === UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can delete the psychologist.
     */
    public function delete(User $user): bool
    {
        return $user->role === UserRole::SUPER->value;
    }
}
