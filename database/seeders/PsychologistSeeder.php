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
        $password = Hash::make(config('app.default_password', 'password'));

        // Seed primary psychologist: Ermin Emilia, M.Psi., Psikolog
        $ermin = User::firstOrCreate(
            ['username' => 'ermin_emilia'],
            [
                'name'       => 'Ermin Emilia, M.Psi., Psikolog',
                'identifier' => 'STR-19850412-202102-2-001',
                'phone'      => '081298765432',
                'role'       => UserRole::PSYCHOLOGIST->value,
                'password'   => $password,
                'verified'   => true,
                'school_id'  => 1,
            ]
        );

        PsychologistProfile::firstOrCreate(
            ['user_id' => $ermin->id],
            [
                'str_number'       => 'STR-19850412-202102-2-001',
                'specialization'   => 'Psikologi Klinis Anak & Remaja',
                'institution_name' => 'Puskesmas Kec. Menteng',
                'phone_number'     => '081298765432',
                'is_active'        => true,
            ]
        );

        // Also seed Dr. Sarah Wijaya for existing references/tests
        $sarah = User::firstOrCreate(
            ['username' => 'sarah.wijaya@puskesmas-menteng.id'],
            [
                'name'       => 'Dr. Sarah Wijaya, M.Psi., Psikolog',
                'identifier' => 'STR-PSI-00101',
                'phone'      => '081298765433',
                'role'       => UserRole::PSYCHOLOGIST->value,
                'password'   => $password,
                'verified'   => true,
                'school_id'  => 1,
            ]
        );

        PsychologistProfile::firstOrCreate(
            ['user_id' => $sarah->id],
            [
                'str_number'       => 'STR-PSI-00101',
                'specialization'   => 'Psikologi Klinis Anak & Remaja',
                'institution_name' => 'Puskesmas Kec. Menteng',
                'phone_number'     => '081298765433',
                'is_active'        => true,
            ]
        );

        $this->command->info('PsychologistSeeder: Ermin Emilia and Dr. Sarah Wijaya seeded successfully.');
    }
}
