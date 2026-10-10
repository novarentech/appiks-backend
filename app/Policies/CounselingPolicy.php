<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Counseling;
use App\Models\User;
use App\Support\CaseTimelineVisibility;

class CounselingPolicy
{
    /**
     * Determine whether the user can store clinical outcome logs for the counseling session.
     */
    public function storeLog(User $user, Counseling $counseling): bool
    {
        // Must be the specific counselor assigned to the counseling session
        return $counseling->counselor_id == $user->id;
    }

    /**
     * Determine whether the student can view this counseling session.
     */
    public function viewStudent(User $user, Counseling $counseling): bool
    {
        return $counseling->student_id == $user->id || $counseling->psychologist_id == $user->id || $counseling->counselor_id == $user->id;
    }

    /**
     * Siapa boleh melihat jejak penanganan sebuah sesi konseling.
     * Satu tempat saja yang perlu diubah saat akses peran lain dibuka —
     * lihat juga CaseTimelineVisibility::RULES.
     */
    public function viewTimeline(User $user, Counseling $counseling): bool
    {
        if (! CaseTimelineVisibility::isAllowed($user->role)) {
            return false;
        }

        return match ($user->role) {
            UserRole::HEADTEACHER->value => $user->school_id !== null
                && $user->school_id == $counseling->student?->school_id,

            // Rencana saat akses dibuka nanti:
            // UserRole::STUDENT->value      => $counseling->student_id == $user->id,
            // UserRole::COUNSELOR->value    => $counseling->counselor_id == $user->id,
            // UserRole::PSYCHOLOGIST->value => $counseling->psychologist_id == $user->id,
            // UserRole::SUPER->value        => true,
            default => false,
        };
    }

    /**
     * Hanya siswa pemilik sesi yang boleh menyetujui atau menolak jadwal
     * yang diajukan Guru BK.
     */
    public function acknowledge(User $user, Counseling $counseling): bool
    {
        return $counseling->student_id == $user->id;
    }

    /**
     * Membatalkan sesi dan mengajukan ulang jadwal adalah wewenang Guru BK
     * yang ditugaskan pada sesi itu.
     */
    public function manageSchedule(User $user, Counseling $counseling): bool
    {
        return $counseling->counselor_id == $user->id;
    }
}
