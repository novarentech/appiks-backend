<?php

namespace App\Policies;

use App\Enums\MoodStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, Report $report): bool
    {
        return $report->user_id == $user->id || $report->user->counselor_id == $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role == UserRole::STUDENT->value && in_array($user->last_mood, [MoodStatus::ANGRY->value, MoodStatus::SAD->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        return $user->role == UserRole::COUNSELOR->value && $user->school_id == $report->user->school_id;
    }
}
