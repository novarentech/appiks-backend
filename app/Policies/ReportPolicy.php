<?php

namespace App\Policies;

use App\Enums\MoodStatus;
use App\Enums\UserRole;
use App\Models\Counseling;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, Counseling|Report $report): bool
    {
        $studentId = $report->student_id ?? $report->user_id;
        $counselorId = $report->counselor_id ?? $report->user?->counselor_id;
        return $studentId == $user->id || $counselorId == $user->id;
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
    public function update(User $user, Counseling|Report $report): bool
    {
        $student = $report->student ?? $report->user;
        return $user->role == UserRole::COUNSELOR->value && $user->school_id == $student?->school_id;
    }

    /**
     * Hanya counselor yang boleh melihat dashboard-data report.
     */
    public function viewGraph(User $authUser): bool
    {
        return $authUser->role === UserRole::COUNSELOR->value
            || $authUser->role === UserRole::SUPER->value
            || $authUser->role === UserRole::HEADTEACHER->value;
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

    /**
     * Determine whether the user can schedule a meeting from the report.
     */
    public function scheduleMeeting(User $user, Counseling|Report $report): bool
    {
        $student = $report->student ?? $report->user;
        return $user->role === UserRole::SUPER->value
            || $user->role === UserRole::ADMIN->value
            || $user->role === UserRole::HEADTEACHER->value
            || ($user->role === UserRole::COUNSELOR->value && ($student?->counselor_id == $user->id || $report->counselor_id == $user->id));
    }
}
