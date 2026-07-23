<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\PsychologistProfile;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PsychologistSeeder extends Seeder
{
    /**
     * Credentials (for developer reference):
     *   username : sarah.wijaya@puskesmas-menteng.id
     *   password : config('app.default_password') (default password in env)
     */
    public function run(): void
    {
        $username = 'sarah.wijaya@puskesmas-menteng.id';

        // Idempotency guard — skip if already seeded
        if (User::where('username', $username)->exists()) {
            $this->command->info('PsychologistSeeder: Dr. Sarah Wijaya already exists, skipping.');
            return;
        }

        $user = User::create([
            'name'       => 'Dr. Sarah Wijaya, M.Psi., Psikolog',
            'username'   => $username,
            'identifier' => 'STR-PSI-00101',
            'phone'      => '081298765432',
            'role'       => UserRole::PSYCHOLOGIST->value,
            'password'   => Hash::make(config('app.default_password')),
            'verified'   => true,
            'school_id'  => 1
        ]);

        PsychologistProfile::create([
            'user_id'          => $user->id,
            'str_number'       => 'STR-PSI-00101',
            'specialization'   => 'Psikologi Klinis Anak & Remaja',
            'institution_name' => 'Puskesmas Kec. Menteng',
            'phone_number'     => '081298765432',
            'is_active'        => true,
        ]);

        $this->command->info('PsychologistSeeder: Dr. Sarah Wijaya seeded successfully.');
    }
}
