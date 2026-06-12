<?php

namespace App\Policies;

use App\Models\CounselingConsent;
use App\Models\User;

class CounselingConsentPolicy
{
    /**
     * Determine whether the user can view the consent request.
     */
    public function view(User $user, CounselingConsent $consent): bool
    {
        return $consent->counseling->student_id == $user->id;
    }

    /**
     * Determine whether the student can update/submit this consent request.
     */
    public function update(User $user, CounselingConsent $consent): bool
    {
        return $consent->counseling->student_id == $user->id;
    }
}
