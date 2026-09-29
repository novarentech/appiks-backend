<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Sharing;
use App\Models\NlpAnalysis;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\ReportStatus;
use App\Jobs\ProcessNlpAnalysisJob;

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
        
        User::create([
            'name' => "Kepala Sekolah",
            'username' => "kepsek",
            'verified'=>true,
            'identifier' => "KS001",
            'password' => $password,
            'role' => UserRole::HEADTEACHER->value,
            'school_id' => 1,
        ]);
        User::create([
            'name' => "Guru TU",
            'username' => "admintu",
            'verified'=>true,
            'identifier' => "TU001",
            'password' => $password,
            'role' => UserRole::ADMIN->value,
            'school_id' => 1,
        ]);
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
        for ($i=1; $i <= 4 ; $i++) { 
           $sis = User::create([
                'name' => "Siswa Demo ".$i,
                'username' => "siswa". $i,
                'verified'=>true,
                'identifier' => "SW000". $i,
                'password' => $password,
                'role' => UserRole::STUDENT->value,
                'counselor_id' => $bkDemo->id,
                'school_id' => 1,
            ]);
            $sharingKuning = Sharing::create([
                'user_id' => $sis->id,
                'title' => 'Merasa Hampa',
                'description' => 'Akhir-akhir ini rasanya hampa, aku gagal terus di semua hal.',
                'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
                'priority' => 'rendah',
            ]);
    
            $nlpAnalysisKuning = $sharingKuning->nlp()->create([
                'text' => $sharingKuning->description,
            ]);

            ProcessNlpAnalysisJob::dispatchSync($nlpAnalysisKuning);
    
            // Kasus Merah
            $sharingMerah = Sharing::create([
                'user_id' => $sis->id,
                'title' => 'Capek Banget',
                'description' => 'Capek banget, kadang kepikiran mau mati aja.',
                'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
                'priority' => 'tinggi',
            ]);
    
            $nlpAnalysisMerah = $sharingMerah->nlp()->create([
                'text' => $sharingMerah->description,
            ]);

            ProcessNlpAnalysisJob::dispatchSync($nlpAnalysisMerah);

            // Kasus Netral / Pembanding (No Trigger)
            $sharingNetral = Sharing::create([
                'user_id' => $sis->id,
                'title' => 'Kegiatan Belajar Hari Ini',
                'description' => 'Hari ini aku belajar kelompok bersama teman sekelas dan tugas selesai dengan lancar.',
                'status' => ReportStatus::MENUNGGU_TANGGAPAN->value,
                'priority' => 'rendah',
            ]);

            $nlpAnalysisNetral = $sharingNetral->nlp()->create([
                'text' => $sharingNetral->description,
            ]);

            ProcessNlpAnalysisJob::dispatchSync($nlpAnalysisNetral);
        }

        $this->call([
            PsychologistSeeder::class,
            PsychologistSlotSeeder::class,
            ReferralFlowSeeder::class,
        ]);
    }
}
