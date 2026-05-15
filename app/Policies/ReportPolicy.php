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

    /**
     * Hanya counselor yang boleh melihat dashboard-data report.
     */
    public function viewGraph(User $authUser): bool
    {
        return $authUser->role === UserRole::COUNSELOR->value
            || $authUser->role === UserRole::SUPER->value;
    }

    /**
     * Hanya Super Admin yang boleh melihat semua report milik student tertentu.
     */
    public function viewStudentReports(User $authUser, User $targetUser): bool
    {
        return $authUser->role === UserRole::SUPER->value
            && $targetUser->role === UserRole::STUDENT->value;
    }

    /**
     * Hanya student yang boleh melihat 2 latest reportnya sendiri.
     */
    public function viewLatest(User $authUser): bool
    {
        return $authUser->role === UserRole::STUDENT->value;
    }
}
