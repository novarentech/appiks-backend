<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Sharing;
use App\Models\NlpAnalysis;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\ReportStatus;

class DemoCaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $password = Hash::make('password');
        $this->call([
            LocationSeeder::class,
            SchoolSeeder::class
        ]);
User::factory()->create([
            'username' => 'super',
            'verified' => true,
            'role' => UserRole::SUPER->value,
            'counselor_id' => null,
            'mentor_id' => null,
            'room_id' => null,
            'school_id' => 1,
        ]);
        
        // 1-5 Guru BK
        $counselors = [];
        for ($i = 1; $i <= 5; $i++) {
            $counselors[$i] = User::create([
                'name' => "Guru BK 0{$i}",
                'verified'=>true,
                'username' => "bk0{$i}",
                'identifier' => "BK" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'password' => $password,
                'role' => UserRole::COUNSELOR->value,
                'school_id' => 1,
            ]);
        }
        
        // 6-10 Siswa
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $students[$i] = User::create([
                'name' => "Siswa 0{$i}",
                'username' => "siswa0{$i}",
                'verified'=>true,
                'identifier' => "SW" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'password' => $password,
                'role' => UserRole::STUDENT->value,
                'counselor_id' => $counselors[$i]->id,
                'school_id' => 1,
            ]);
        }
        
        // 11 Guru BK Demo
        $bkDemo = User::create([
            'name' => "Guru BK Demo",
            'username' => "bkdemo",
            'verified'=>true,
            'identifier' => "BK9999",
            'password' => $password,
            'role' => UserRole::COUNSELOR->value,
            'school_id' => 1,
        ]);
        
        // 12 Siswa Demo 1 & 2
        $siswaDemo1 = User::create([
            'name' => "Siswa Demo 1",
            'username' => "siswademo",
            'verified'=>true,
            'identifier' => "SW9991",
            'password' => $password,
            'role' => UserRole::STUDENT->value,
            'counselor_id' => $bkDemo->id,
            'school_id' => 1,
        ]);

        $siswaDemo2 = User::create([
            'name' => "Siswa Demo 2",
            'username' => "siswademo2",
            'verified'=>true,
            'identifier' => "SW9992",
            'password' => $password,
            'role' => UserRole::STUDENT->value,
            'counselor_id' => $bkDemo->id,
            'school_id' => 1,
        ]);

        $allStudents = array_merge(array_values($students), [$siswaDemo1, $siswaDemo2]);

        // Setiap siswa memiliki kasus curhat yang sudah melewati NLP (Zona Kuning & Merah)
        foreach ($allStudents as $siswa) {
            // Kasus Kuning
            $sharingKuning = Sharing::create([
                'user_id' => $siswa->id,
                'title' => 'Merasa Hampa',
                'description' => 'Akhir-akhir ini rasanya hampa, aku gagal terus di semua hal.',
                'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
                'priority' => 'rendah',
            ]);

            NlpAnalysis::create([
                'nlpable_type' => Sharing::class,
                'nlpable_id' => $sharingKuning->id,
                'text' => $sharingKuning->description,
                'response' => [
                    'total_score' => 7,
                    'zone_status' => 'Yellow',
                    'matched_keywords' => [
                        ['stem' => 'hampa', 'zone' => 'Yellow', 'weight' => 4],
                        ['stem' => 'gagal', 'zone' => 'Yellow', 'weight' => 3],
                    ]
                ],
                'flag' => 'Yellow',
            ]);

            // Kasus Merah
            $sharingMerah = Sharing::create([
                'user_id' => $siswa->id,
                'title' => 'Capek Banget',
                'description' => 'Capek banget, kadang kepikiran mau mati aja.',
                'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
                'priority' => 'tinggi',
            ]);

            NlpAnalysis::create([
                'nlpable_type' => Sharing::class,
                'nlpable_id' => $sharingMerah->id,
                'text' => $sharingMerah->description,
                'response' => [
                    'total_score' => 9,
                    'zone_status' => 'Red',
                    'matched_keywords' => [
                        ['stem' => 'mau mati', 'zone' => 'Red', 'weight' => 9],
                    ]
                ],
                'flag' => 'Red',
            ]);
        }

        $this->call([
            PsychologistSeeder::class,
            PsychologistSlotSeeder::class,
            ReferralFlowSeeder::class,
        ]);
    }
}
