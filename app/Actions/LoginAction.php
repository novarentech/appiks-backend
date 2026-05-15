<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
    public function handle(array $credentials): ?array
    {
        $user = User::with(['school', 'room', 'mentor'])
            ->where('username', strtolower($credentials['username']))
            ->first();

        if (!$user) {
            return null;
        }

        $token = Auth::claims([
            'name'       => $user->name,
            'username'   => $user->username,
            'identifier' => $user->identifier,
            'role'       => $user->role,
            'verified'   => $user->verified,
            'room'       => $user->room->name ?? null,
            'mentor'     => $user->mentor->name ?? null,
            'school'     => $user->school->name ?? null,
        ])->attempt($credentials);

        if (!$token) {
            return null;
        }

        return [
            'token'     => $token,
            'expiresIn' => now()
                ->addMinutes(Auth::factory()->getTTL())
                ->setTimezone(config('app.timezone'))
                ->toIso8601String(),
        ];
    }
}
