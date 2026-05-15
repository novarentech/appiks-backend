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
        return $sharing->user_id == $user->id || $sharing->user->counselor_id == $user->id;
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
        return $user->role == UserRole::COUNSELOR->value && $user->id == $sharing->user->counselor_id;
    }

    /**
     * Hanya counselor yang boleh melihat dashboard-data sharing.
     */
    public function viewGraph(User $authUser): bool
    {
        return $authUser->role === UserRole::COUNSELOR->value
            || $authUser->role === UserRole::SUPER->value;
    }

    /**
     * Hanya Super Admin yang boleh melihat semua sharing milik student tertentu.
     */
    public function viewStudentSharing(User $authUser, User $targetUser): bool
    {
        return $authUser->role === UserRole::SUPER->value
            && $targetUser->role === UserRole::STUDENT->value;
    }
}
