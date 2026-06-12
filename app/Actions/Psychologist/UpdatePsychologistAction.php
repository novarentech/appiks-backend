<?php

namespace App\Actions\Psychologist;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdatePsychologistAction
{
    /**
     * Update a psychologist and their profile.
     */
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $userUpdate = [
                'name' => $data['name'],
                'username' => strtolower($data['email']),
                'identifier' => $data['str_number'],
                'phone' => $data['phone_number'] ?? null,
            ];

            if (!empty($data['password'])) {
                $userUpdate['password'] = Hash::make($data['password']);
            }

            $user->update($userUpdate);

            $profileData = [
                'str_number' => $data['str_number'],
                'specialization' => $data['specialization'] ?? null,
                'institution_name' => $data['institution_name'],
                'phone_number' => $data['phone_number'] ?? null,
            ];

            if (isset($data['is_active'])) {
                $profileData['is_active'] = (bool) $data['is_active'];
            }

            $user->psychologistProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            return $user->load('psychologistProfile');
        });
    }
}
