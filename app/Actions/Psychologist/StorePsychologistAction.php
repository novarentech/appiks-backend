<?php

namespace App\Actions\Psychologist;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\PsychologistProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StorePsychologistAction
{
    /**
     * Store a new psychologist and their profile.
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => strtolower($data['email']), // Email is stored as username
                'identifier' => $data['str_number'],     // STR is stored as identifier
                'phone' => $data['phone_number'] ?? null,
                'role' => UserRole::PSYCHOLOGIST->value,
                'password' => Hash::make('password123'),
                'verified' => true,
            ]);

            $user->psychologistProfile()->create([
                'str_number' => $data['str_number'],
                'specialization' => $data['specialization'] ?? null,
                'institution_name' => $data['institution_name'],
                'phone_number' => $data['phone_number'] ?? null,
                'is_active' => true,
            ]);

            return $user->load('psychologistProfile');
        });
    }
}
