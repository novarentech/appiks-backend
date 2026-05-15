<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MoodRecord;
use App\Models\User;

class MoodRecordPolicy
{
    /**
     * Hanya student yang boleh akses recap per bulan miliknya sendiri.
     */
    public function recapPerMonth(User $authUser): bool
    {
        return $authUser->role === UserRole::STUDENT->value;
    }

    /**
     * Counselor dari student tersebut, Wali dari student tersebut, atau Super Admin.
     */
    public function viewHistory(User $authUser, User $targetUser): bool
    {
        return ($authUser->role === UserRole::COUNSELOR->value && $authUser->id === $targetUser->counselor_id)
            || ($authUser->role === UserRole::TEACHER->value && $authUser->id === $targetUser->mentor_id)
            || $authUser->role === UserRole::SUPER->value;
    }

    /**
     * Hanya Super Admin yang boleh melihat trend mood per sekolah.
     */
    public function viewSchoolTrend(User $authUser): bool
    {
        return $authUser->role === UserRole::SUPER->value;
    }

    /**
     * Hanya Teacher atau Counselor yang boleh export.
     */
    public function export(User $authUser): bool
    {
        return in_array($authUser->role, [
            UserRole::TEACHER->value,
            UserRole::COUNSELOR->value,
        ]);
    }

    /**
     * Hanya student yang boleh melihat recapPerMonth.
     */
    public function store(User $authUser): bool
    {
        return $authUser->role === UserRole::STUDENT->value;
    }
}
