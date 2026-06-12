<?php

namespace App\Actions\Psychologist;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeletePsychologistAction
{
    /**
     * Delete a psychologist and their profile (soft delete).
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->psychologistProfile()?->delete();
            $user->delete();
        });
    }
}
