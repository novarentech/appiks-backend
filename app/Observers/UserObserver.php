<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        if ($user->role == UserRole::STUDENT->value) {
            $user->cloud()->create(['level' => 1]);
        }
    }
}
