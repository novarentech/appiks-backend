<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role == UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, School $school): bool
    {
        return $user->role == UserRole::SUPER->value || ($user->school_id == $school->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role == UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, School $school): bool
    {
        return $user->role == UserRole::SUPER->value;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        return $user->role == UserRole::SUPER->value;
    }
}
