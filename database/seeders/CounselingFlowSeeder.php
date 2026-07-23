<?php

namespace Database\Seeders;

use App\Enums\CounselingMethod;
use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
use App\Models\CounselingLog;
use App\Models\NlpAnalysis;
use App\Models\Report;
use App\Models\Sharing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds the complete counseling lifecycle across 4 distinct scenarios:
 *
 *   A) Completed internal counseling (report-sourced)
 *      Report: Diselesaikan
 *      Counseling: selesai + CounselingLog + ClinicalSummary
 *
 *   B) Pending counseling awaiting student acknowledgement (report-sourced)
 *      Report: Menunggu Persetujuan Siswa
 *      Counseling: dijadwalkan (no logs yet)
 *
 *   C) Cancelled counseling (student rejected the schedule)
 *      Report: Jadwal Ditolak Siswa  → then Dibatalkan
 *      Counseling: ditolak
 *
 *   D) NLP-incident counseling (sourced from a high-risk sharing, no report)
 *      Counseling: status = menunggu (NLP-triggered, counselor notified)
 */
class CounselingFlowSeeder extends Seeder
{
    public function run(): void
    {
        $students  = User::where('role', 'student')->where('verified', true)->get();
        $counselor = User::where('role', 'counselor')->first();

        if ($students->isEmpty() || !$counselor) {
            $this->command->warn('CounselingFlowSeeder: Need at least 1 verified student and 1 counselor.');
            return;
        }

        // Distribute students across scenarios (cycle through A→B→C→D)
        foreach ($students as $index => $student) {
            $scenario = $index % 4;

            match ($scenario) {
                0 => $this->seedScenarioA($student, $counselor),
                1 => $this->seedScenarioB($student, $counselor),
                2 => $this->seedScenarioC($student, $counselor),
                3 => $this->seedScenarioD($student, $counselor),
            };
        }

        $this->command->info('CounselingFlowSeeder: All 4 scenarios seeded successfully.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario A — Completed counseling (report-sourced)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioA(User $student, User $counselor): void
    {
        $sessionDate = Carbon::now()->subDays(rand(5, 20));

        // 1. Report: starts at Belum Ditinjau → ends at Diselesaikan
        $report = Report::create([
            'user_id'      => $student->id,
            'counselor_id' => $counselor->id,
            'topic'        => 'Masalah akademik dan tekanan ujian',
            'room'         => 'Ruang BK 1',
            'date'         => $sessionDate->toDateString(),
            'time'         => '09:00',
            'status'       => ReportStatus::SELESAI->value,
            'priority'     => 'tinggi',
            'notes'        => 'Siswa mengeluh tekanan belajar yang berlebihan menjelang ujian nasional.',
            'result'       => 'Siswa bersedia mengikuti sesi konseling reguler seminggu sekali.',
            'created_at'   => $sessionDate->copy()->subDays(3),
            'updated_at'   => $sessionDate,
        ]);

        // 2. Sharing that triggered the report
        $sharing = $this->getOrCreateSharingForStudent($student, $sessionDate->copy()->subDays(4));

        // 3. Counseling: selesai
        $counseling = Counseling::create([
            'source_type'  => 'regular',
            'report_id'    => $report->id,
            'student_id'   => $student->id,
            'counselor_id' => $counselor->id,
            'sharing_id'   => $sharing?->id,
            'room'         => 'Ruang BK 1',
            'notes'        => 'Sesi pertama berjalan dengan baik. Siswa kooperatif.',
            'reason'       => 'Tekanan akademik menjelang ujian.',
            'type'         => 'internal',
            'method'       => CounselingMethod::OFFLINE->value,
            'status'       => CounselingStatus::SELESAI->value,
            'resolution'   => CounselingResolution::NOTPRIORITY->value,
            'scheduled_at' => $sessionDate,
            'cutdown_at'   => $sessionDate->copy()->addHour(),
            'created_at'   => $sessionDate->copy()->subDays(2),
            'updated_at'   => $sessionDate,
        ]);

        // 4. CounselingLog
        CounselingLog::create([
            'counseling_id'     => $counseling->id,
            'student_id'        => $student->id,
            'counselor_id'      => $counselor->id,
            'session_mode'      => CounselingMethod::OFFLINE->value,
            'clinical_notes'    => 'Siswa memperlihatkan tanda-tanda kecemasan ringan. Direkomendasikan teknik relaksasi dan journaling harian.',
            'resolution_status' => CounselingResolution::NOTPRIORITY->value,
            'created_at'        => $sessionDate,
            'updated_at'        => $sessionDate,
        ]);

        // 5. ClinicalSummary
        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => json_encode([
                'chief_complaint'  => 'Tekanan akademik berlebih menjelang UN.',
                'assessment'       => 'Kecemasan ringan, tidak memerlukan rujukan klinis.',
                'plan'             => 'Sesi lanjutan 1x seminggu, teknik relaksasi dan journaling.',
                'session_count'    => 1,
                'resolution'       => CounselingResolution::NOTPRIORITY->value,
            ]),
            'created_at' => $sessionDate,
            'updated_at' => $sessionDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario B — Pending, awaiting student acknowledgement
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioB(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(rand(1, 3));

        $report = Report::create([
            'user_id'      => $student->id,
            'counselor_id' => $counselor->id,
            'topic'        => 'Konflik dengan teman sebaya',
            'room'         => 'Ruang BK 2',
            'date'         => $createdDate->toDateString(),
            'time'         => '10:30',
            'status'       => ReportStatus::MENUNGGU_PERSETUJUAN->value,
            'priority'     => 'rendah',
            'notes'        => 'Dilaporkan ada perselisihan antar siswa di kelas.',
            'result'       => null,
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate,
        ]);

        $sharing = $this->getOrCreateSharingForStudent($student, $createdDate->copy()->subDay());

        // Counseling is scheduled but student hasn't acknowledged yet
        Counseling::create([
            'source_type'  => 'regular',
            'report_id'    => $report->id,
            'student_id'   => $student->id,
            'counselor_id' => $counselor->id,
            'sharing_id'   => $sharing?->id,
            'room'         => 'Ruang BK 2',
            'notes'        => 'Jadwal konseling menunggu konfirmasi siswa.',
            'reason'       => 'Konflik teman sebaya.',
            'type'         => 'internal',
            'method'       => CounselingMethod::OFFLINE->value,
            'status'       => CounselingStatus::DIJADWALKAN->value,
            'resolution'   => null,
            'scheduled_at' => Carbon::now()->addDays(2),
            'cutdown_at'   => null,
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario C — Cancelled (student rejected the schedule)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioC(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(rand(7, 14));

        $report = Report::create([
            'user_id'      => $student->id,
            'counselor_id' => $counselor->id,
            'topic'        => 'Perilaku menyimpang di sekolah',
            'room'         => 'Ruang BK 1',
            'date'         => $createdDate->toDateString(),
            'time'         => '13:00',
            'status'       => ReportStatus::DIBATALKAN->value,
            'priority'     => 'tinggi',
            'notes'        => 'Siswa dilaporkan sering membolos dan mengganggu KBM.',
            'result'       => 'Konseling dibatalkan karena siswa menolak jadwal yang ditetapkan.',
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate->copy()->addDays(2),
        ]);

        $sharing = $this->getOrCreateSharingForStudent($student, $createdDate->copy()->subDay());

        Counseling::create([
            'source_type'  => 'regular',
            'report_id'    => $report->id,
            'student_id'   => $student->id,
            'counselor_id' => $counselor->id,
            'sharing_id'   => $sharing?->id,
            'room'         => 'Ruang BK 1',
            'notes'        => 'Siswa menolak jadwal. Konseling dibatalkan.',
            'reason'       => 'Perilaku menyimpang.',
            'type'         => 'internal',
            'method'       => CounselingMethod::OFFLINE->value,
            'status'       => CounselingStatus::DITOLAK->value,
            'resolution'   => null,
            'scheduled_at' => $createdDate->copy()->addDays(1),
            'cutdown_at'   => null,
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate->copy()->addDays(2),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario D — NLP-incident counseling (sharing-sourced, no report)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioD(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(rand(1, 5));

        // Create a high-risk sharing with NLP flag
        $sharing = Sharing::create([
            'user_id'     => $student->id,
            'title'       => 'Aku tidak kuat lagi menghadapi ini semua',
            'description' => 'Rasanya beban ini terlalu berat. Aku tidak bisa tidur, tidak bisa fokus, dan tidak tahu harus berbuat apa. Kadang berpikir ingin menyerah saja.',
            'reply'       => null,
            'replied_at'  => null,
            'replied_by'  => null,
            'priority'    => 'tinggi',
            'status'      => ReportStatus::DITINJAU->value,
            'created_at'  => $createdDate,
            'updated_at'  => $createdDate,
        ]);

        // NLP analysis detects high-risk
        NlpAnalysis::create([
            'text'         => $sharing->description,
            'response'     => json_encode([
                'total_score'      => 95,
                'zone_status'      => 'red',
                'matched_keywords' => [
                    ['stem' => 'menyerah', 'zone' => 'red', 'weight' => 40],
                    ['stem' => 'tidak kuat', 'zone' => 'red', 'weight' => 35],
                    ['stem' => 'tidak bisa tidur', 'zone' => 'yellow', 'weight' => 20],
                ],
            ]),
            'flag'         => 'high-risk',
            'status'       => 'true-positive',
            'reason'       => 'Terdeteksi indikator kritis pada teks curhat siswa (zona merah).',
            'nlpable_type' => Sharing::class,
            'nlpable_id'   => $sharing->id,
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate,
        ]);

        // Counseling triggered automatically by NLP incident — no report_id
        Counseling::create([
            'source_type'  => 'nlp_incident',
            'report_id'    => null,
            'student_id'   => $student->id,
            'counselor_id' => $counselor->id,
            'sharing_id'   => $sharing->id,
            'room'         => null,
            'notes'        => 'Konseling dipicu otomatis oleh sistem NLP (zona merah). Menunggu tindakan konselor.',
            'reason'       => 'Indikator kritis terdeteksi di curhat siswa.',
            'type'         => 'internal',
            'method'       => null,
            'status'       => CounselingStatus::MENUNGGU->value,
            'resolution'   => null,
            'scheduled_at' => null,
            'cutdown_at'   => null,
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helper: fetch the most recent sharing for this student, or create one
    // ────────────────────────────────────────────────────────────────────────
    private function getOrCreateSharingForStudent(User $student, Carbon $date): ?Sharing
    {
        $existing = Sharing::where('user_id', $student->id)->latest()->first();
        if ($existing) {
            return $existing;
        }

        return Sharing::create([
            'user_id'     => $student->id,
            'title'       => 'Saya butuh bantuan',
            'description' => 'Saya merasa kesulitan dan butuh bantuan dari guru BK.',
            'reply'       => null,
            'replied_at'  => null,
            'replied_by'  => null,
            'priority'    => 'rendah',
            'status'      => ReportStatus::MENUNGGU_TINJAUAN->value,
            'created_at'  => $date,
            'updated_at'  => $date,
        ]);
    }
}
