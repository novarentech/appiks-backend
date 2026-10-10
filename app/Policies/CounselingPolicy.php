<?php

namespace App\Policies;

use App\Models\Counseling;
use App\Models\User;

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
