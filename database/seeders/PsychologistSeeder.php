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
            ['username' => 'ermin'],
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
                'institution_name' => 'Puskesmas Jetis',
                'phone_number'     => '081298765432',
                'is_active'        => true,
            ]
        );
        $yulia = User::firstOrCreate(
            ['username' => 'yulia'],
            [
                'name'       => 'Yulia Mukti Rufaida, M.Psi., Psikolog',
                'identifier' => 'STR-19850412-202102-2-002',
                'phone'      => '081298765431',
                'role'       => UserRole::PSYCHOLOGIST->value,
                'password'   => $password,
                'verified'   => true,
                'school_id'  => 1,
            ]
        );

        PsychologistProfile::firstOrCreate(
            ['user_id' => $yulia->id],
            [
                'str_number'       => 'STR-19850412-202102-2-002',
                'specialization'   => 'Psikologi Klinis Anak & Remaja',
                'institution_name' => 'Biro Psikologi Dinamis',
                'phone_number'     => '081298765431',
                'is_active'        => true,
            ]
        );

        $this->command->info('PsychologistSeeder: Ermin Emilia and Yulia Mukti Rufaida seeded successfully.');
    }
}
