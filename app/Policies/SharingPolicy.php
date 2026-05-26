<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Sharing;
use App\Models\User;

class SharingPolicy
{
    public function view(User $user, Sharing $sharing): bool
    {
        return $sharing->user_id == $user->id || $sharing->user->counselor_id == $user->id;
    }

    public function falsePositive(User $user, Sharing $sharing): bool
    {
        return $sharing->user->counselor_id == $user->id && $sharing->status == ReportStatus::MENUNGGU->value;
    }

    public function create(User $user): bool
    {
        return $user->role == UserRole::STUDENT->value;
    }

    public function update(User $user, Sharing $sharing): bool
    {
        return $user->role == UserRole::COUNSELOR->value && $user->id == $sharing->user->counselor_id;
    }

    public function viewGraph(User $authUser): bool
    {
        return $authUser->role === UserRole::COUNSELOR->value
            || $authUser->role === UserRole::SUPER->value;
    }

    public function viewStudentSharing(User $authUser, User $targetUser): bool
    {
        return $authUser->role === UserRole::SUPER->value
            && $targetUser->role === UserRole::STUDENT->value;
    }
}
