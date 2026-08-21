<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\ConsentStatus;
use App\Enums\CounselingMethod;
use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use App\Enums\SlotStatus;
use App\Models\BookingSchedule;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
use App\Models\CounselingConsent;
use App\Models\CounselingLog;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use App\Models\Report;
use App\Models\Sharing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds comprehensive referral scenarios for demo and testing.
 *
 * Scenarios:
 *   A) 3 Kategori (Mood + Red Zone + Asesmen BK) — Confirmed & Selesai dengan Ringkasan AI + Catatan Klinis
 *   B) 1 Kategori (Mood Saja) — Menunggu Konfirmasi #1 (Pending, SLA aktif 24 jam)
 *   C) 2 Kategori (Curhat Red Zone + Asesmen BK) — Menunggu Konfirmasi #2 (Pending, SLA aktif 24 jam)
 *   D) 1 Kategori (Catatan Asesmen BK Saja) — Kedaluwarsa (Expired, deadline lampau)
 *   E) 3 Kategori — Terkonfirmasi Aktif (Jadwal konsultasi Senin 09:00 WIB)
 *   F) Consent Pending — Siswa belum memilih persetujuan data
 */
class ReferralFlowSeeder extends Seeder
{
    private PsychologistProfile $psychologistProfile;

    public function run(): void
    {
        // Prioritize Ermin Emilia, M.Psi., Psikolog, fallback to first psychologist
        $psychUser = User::where('username', 'ermin_emilia')->first()
            ?? User::where('role', 'psychologist')->first();

        if (!$psychUser) {
            $this->command->warn('ReferralFlowSeeder: No psychologist user found. Run PsychologistSeeder first.');
            return;
        }

        $this->psychologistProfile = PsychologistProfile::where('user_id', $psychUser->id)->first();

        if (!$this->psychologistProfile) {
            $this->command->warn('ReferralFlowSeeder: PsychologistProfile not found. Run PsychologistSeeder first.');
            return;
        }

        $students  = User::where('role', 'student')->where('verified', true)->get();
        $counselor = User::where('role', 'counselor')->first();

        if ($students->count() < 4) {
            $this->command->warn('ReferralFlowSeeder: At least 4 verified students are required.');
            return;
        }

        // Scenario 1: 3 Kategori (Mood + Red Zone + Asesmen BK) -> Selesai
        $this->seedScenario3CategoriesCompleted($students[0], $counselor);

        // Scenario 2: 1 Kategori (Mood Saja) -> Menunggu Konfirmasi #1
        $this->seedScenarioMoodOnlyPending($students[1], $counselor);

        // Scenario 3: 2 Kategori (Curhat + Asesmen BK) -> Menunggu Konfirmasi #2
        $this->seedScenarioSharingAssessmentPending($students[2], $counselor);

        // Scenario 4: 1 Kategori (Asesmen BK Saja) -> Kedaluwarsa
        $this->seedScenarioAssessmentOnlyExpired($students[3], $counselor);

        // Scenario 5: Terkonfirmasi Aktif
        if (isset($students[4])) {
            $this->seedScenarioConfirmedUpcoming($students[4], $counselor);
        }

        // Scenario 6: Consent Pending
        if (isset($students[5])) {
            $this->seedScenarioConsentPending($students[5], $counselor);
        }

        $this->command->info('ReferralFlowSeeder: All referral scenarios seeded successfully for Ermin Emilia.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario 1 — 3 Kategori: Mood + Curhat Red Zone + Catatan Asesmen BK (Selesai)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenario3CategoriesCompleted(User $student, User $counselor): void
    {
        $sessionDate = Carbon::now()->subDays(2);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::SELESAI, $sessionDate->copy()->subDays(8));

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::SELESAI,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: $sessionDate,
        );

        // Consent: 3 kategori disetujui
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => ['mood_history', 'sharing_history', 'assesment_logs'],
            'granted_at'    => $sessionDate->copy()->subDays(2),
            'rejected_at'   => null,
            'created_at'    => $sessionDate->copy()->subDays(4),
            'updated_at'    => $sessionDate->copy()->subDays(2),
        ]);

        $slot = $this->createSlot(
            date: $sessionDate->toDateString(),
            startTime: '08:00:00',
            endTime: '09:00:00',
            status: SlotStatus::CONFIRMED,
        );

        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::CONFIRMED->value,
            'deadline_at'   => $sessionDate->copy()->subDays(1),
            'location'      => 'Puskesmas Kec. Menteng, Lt. 2, Ruang Konseling',
            'created_at'    => $sessionDate->copy()->subDays(2),
            'updated_at'    => $sessionDate->copy()->subDays(1),
        ]);

        CounselingLog::create([
            'counseling_id'     => $counseling->id,
            'student_id'        => $student->id,
            'counselor_id'      => $counselor->id,
            'session_mode'      => CounselingMethod::OFFLINE->value,
            'clinical_notes'    => 'Siswa menangis saat sesi konseling awal, mengeluhkan kelelahan mental akibat tuntutan nilai. Memerlukan penanganan psikologis intensif.',
            'resolution_status' => CounselingResolution::NEEDMORE->value,
            'created_at'        => $sessionDate->copy()->subDays(5),
            'updated_at'        => $sessionDate->copy()->subDays(5),
        ]);

        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => 'Siswa mengalami penurunan mood signifikan selama 30 hari terakhir dengan pola kecemasan berulang menjelang ujian. Curhat Red Zone mengindikasikan keputusasaan akibat ekspektasi akademik. Catatan asesmen Guru BK mengonfirmasi penarikan diri sosial dan penurunan performa belajar. Direkomendasikan terapi kognitif perilaku (CBT) dan pemantauan berkala.',
            'raw_payload'   => [
                'mood_history' => [
                    ['date' => Carbon::now()->subDays(10)->toDateString(), 'mood' => 'sedih', 'notes' => 'Merasa sangat lelah'],
                    ['date' => Carbon::now()->subDays(7)->toDateString(), 'mood' => 'cemas', 'notes' => 'Takut tidak lulus'],
                    ['date' => Carbon::now()->subDays(4)->toDateString(), 'mood' => 'buruk', 'notes' => 'Tidak bisa tidur nyenyak'],
                ],
                'sharing_history' => [
                    [
                        'title'       => 'Capek Banget',
                        'description' => 'Capek banget, kadang kepikiran mau mati aja.',
                        'priority'    => 'tinggi',
                        'date'        => Carbon::now()->subDays(8)->toDateString(),
                    ]
                ],
                'assesment_logs' => [
                    [
                        'session_mode'   => 'offline',
                        'clinical_notes' => 'Siswa menangis saat sesi konseling awal, mengeluhkan kelelahan mental akibat tuntutan nilai.',
                        'date'           => Carbon::now()->subDays(5)->toDateString(),
                    ]
                ],
            ],
            'clinical_notes'       => 'Pasien menunjukkan perbaikan afek setelah intervensi awal. Sesi lanjutan direkomendasikan.',
            'rating'               => 'good',
            'improvement_feedback' => 'Ringkasan komprehensif dan sangat akurat merangkum 3 sumber data.',
            'created_at'           => $sessionDate,
            'updated_at'           => $sessionDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario 2 — 1 Kategori (Mood Saja): Menunggu Konfirmasi #1 (Pending SLA Aktif)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioMoodOnlyPending(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subHours(6);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::DIJADWALKAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DIJADWALKAN,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: Carbon::now()->startOfWeek()->addWeek()->setTime(8, 0),
        );

        // Consent: 1 kategori (mood_history)
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => ['mood_history'],
            'granted_at'    => $createdDate->copy()->addHour(),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addHour(),
        ]);

        $slot = $this->createSlot(
            date: Carbon::now()->startOfWeek()->addWeek()->toDateString(),
            startTime: '08:00:00',
            endTime: '09:00:00',
            status: SlotStatus::TENTATIVE,
        );

        // Booking: Pending konfirmasi #1 (deadline aktif 18 jam ke depan)
        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::PENDING->value,
            'deadline_at'   => Carbon::now()->addHours(18),
            'location'      => 'Puskesmas Kec. Menteng',
            'created_at'    => $createdDate->copy()->addHour(),
            'updated_at'    => $createdDate->copy()->addHour(),
        ]);

        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => 'Berdasarkan riwayat mood 14 hari terakhir, siswa mengalami fluktuasi emosional yang konsisten pada rentang rendah (sedih dan tertekan). Pola mood memburuk pada sore hari. Tidak ada data curhat maupun catatan asesmen yang dibagikan sesuai batas izin siswa.',
            'raw_payload'   => [
                'mood_history' => [
                    ['date' => Carbon::now()->subDays(6)->toDateString(), 'mood' => 'sedih', 'notes' => 'Murung sepanjang hari'],
                    ['date' => Carbon::now()->subDays(3)->toDateString(), 'mood' => 'tertekan', 'notes' => 'Sulit konsentrasi di kelas'],
                    ['date' => Carbon::now()->subDays(1)->toDateString(), 'mood' => 'cemas', 'notes' => 'Khawatir ujian besok'],
                ],
                'sharing_history' => [],
                'assesment_logs'  => [],
            ],
            'created_at' => $createdDate->copy()->addHour(),
            'updated_at' => $createdDate->copy()->addHour(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario 3 — 2 Kategori (Curhat + Asesmen BK): Menunggu Konfirmasi #2 (Pending SLA Aktif)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioSharingAssessmentPending(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subHours(2);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::DIJADWALKAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DIJADWALKAN,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: Carbon::now()->startOfWeek()->addWeek()->addDays(2)->setTime(9, 0),
        );

        // Consent: 2 kategori (sharing_history, assesment_logs)
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => ['sharing_history', 'assesment_logs'],
            'granted_at'    => $createdDate->copy()->addMinutes(30),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addMinutes(30),
        ]);

        $slot = $this->createSlot(
            date: Carbon::now()->startOfWeek()->addWeek()->addDays(2)->toDateString(),
            startTime: '09:00:00',
            endTime: '10:00:00',
            status: SlotStatus::TENTATIVE,
        );

        // Booking: Pending konfirmasi #2 (deadline aktif 22 jam ke depan)
        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::PENDING->value,
            'deadline_at'   => Carbon::now()->addHours(22),
            'location'      => 'Puskesmas Kec. Menteng',
            'created_at'    => $createdDate->copy()->addMinutes(30),
            'updated_at'    => $createdDate->copy()->addMinutes(30),
        ]);

        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => 'Siswa mengungkapkan keluhan intensitas tinggi dalam curhat Red Zone terkait tekanan perundungan daring dan rasa tidak aman. Catatan asesmen Guru BK mencatat adanya perubahan perilaku mendadak dan isolasi mandiri di kelas. Riwayat mood harian tidak disertakan dalam persetujuan.',
            'raw_payload'   => [
                'mood_history' => [],
                'sharing_history' => [
                    [
                        'title'       => 'Diteror di Media Sosial',
                        'description' => 'Saya merasa tidak aman ke sekolah karena terus diteror dan diancam di media sosial.',
                        'priority'    => 'tinggi',
                        'date'        => Carbon::now()->subDays(2)->toDateString(),
                    ]
                ],
                'assesment_logs' => [
                    [
                        'session_mode'   => 'offline',
                        'clinical_notes' => 'Siswa menunjukkan tanda trauma akut dan gemetar saat menceritakan insiden perundungan.',
                        'date'           => Carbon::now()->subDays(1)->toDateString(),
                    ]
                ],
            ],
            'created_at' => $createdDate->copy()->addMinutes(30),
            'updated_at' => $createdDate->copy()->addMinutes(30),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario 4 — 1 Kategori (Asesmen BK Saja): Kedaluwarsa (Expired Past Deadline)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioAssessmentOnlyExpired(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(6);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::DIJADWALKAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DIJADWALKAN,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: Carbon::now()->addDays(2),
        );

        // Consent: 1 kategori (assesment_logs)
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => ['assesment_logs'],
            'granted_at'    => $createdDate->copy()->addDay(),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addDay(),
        ]);

        $slot = $this->createSlot(
            date: Carbon::now()->addDays(2)->toDateString(),
            startTime: '10:00:00',
            endTime: '11:00:00',
            status: SlotStatus::AVAILABLE,
        );

        // Booking: Kedaluwarsa (deadline 12 jam yang lalu)
        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::EXPIRED->value,
            'deadline_at'   => Carbon::now()->subHours(12),
            'location'      => null,
            'created_at'    => $createdDate->copy()->addDay(),
            'updated_at'    => Carbon::now()->subHours(12),
        ]);

        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => 'Evaluasi klinis Guru BK menunjukkan siswa mengalami kecemasan sosial ringan hingga sedang tanpa indikasi krisis darurat. Riwayat mood dan curhat siswa tidak disertakan dalam persetujuan.',
            'raw_payload'   => [
                'mood_history'    => [],
                'sharing_history' => [],
                'assesment_logs'  => [
                    [
                        'session_mode'   => 'offline',
                        'clinical_notes' => 'Konseling awal mengenai kesulitan adaptasi dengan teman sekelas baru.',
                        'date'           => $createdDate->toDateString(),
                    ]
                ],
            ],
            'created_at' => $createdDate->copy()->addDay(),
            'updated_at' => $createdDate->copy()->addDay(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario 5 — Terkonfirmasi Aktif (Jadwal Konsultasi Mendatang)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioConfirmedUpcoming(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(1);
        $slotDate = Carbon::now()->startOfWeek()->addWeek()->toDateString(); // Next Monday

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::DIJADWALKAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DIJADWALKAN,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: Carbon::parse($slotDate)->setTime(9, 0),
        );

        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => ['mood_history', 'sharing_history', 'assesment_logs'],
            'granted_at'    => $createdDate->copy()->addHours(2),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addHours(2),
        ]);

        $slot = $this->createSlot(
            date: $slotDate,
            startTime: '09:00:00',
            endTime: '10:00:00',
            status: SlotStatus::CONFIRMED,
        );

        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::CONFIRMED->value,
            'deadline_at'   => $createdDate->copy()->addHours(24),
            'location'      => 'Puskesmas Kec. Menteng, Lt. 2, Ruang Konseling',
            'created_at'    => $createdDate->copy()->addHours(2),
            'updated_at'    => $createdDate->copy()->addHours(4),
        ]);

        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => 'Siswa menunjukkan stres akademik berkepanjangan dengan gangguan pola tidur. Curhat Red Zone mencatat perasaan tidak berharga saat mendapat nilai di bawah target. Asesmen Guru BK merekomendasikan intervensi manajemen stres dan konseling keluarga.',
            'raw_payload'   => [
                'mood_history' => [
                    ['date' => Carbon::now()->subDays(3)->toDateString(), 'mood' => 'cemas', 'notes' => 'Stres memikirkan tryout'],
                ],
                'sharing_history' => [
                    [
                        'title'       => 'Merasa Gagal',
                        'description' => 'Saya merasa selalu mengecewakan orang tua saya.',
                        'priority'    => 'tinggi',
                        'date'        => Carbon::now()->subDays(2)->toDateString(),
                    ]
                ],
                'assesment_logs' => [
                    [
                        'session_mode'   => 'offline',
                        'clinical_notes' => 'Siswa menunjukkan perfeksionisme maladaptif dan butuh konseling regulasi emosi.',
                        'date'           => Carbon::now()->subDays(1)->toDateString(),
                    ]
                ],
            ],
            'created_at' => $createdDate->copy()->addHours(2),
            'updated_at' => $createdDate->copy()->addHours(2),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario 6 — Consent Pending
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioConsentPending(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subHours(4);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::MENUNGGU_PERSETUJUAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DIJADWALKAN,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: null,
        );

        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::PENDING->value,
            'scopes'        => null,
            'granted_at'    => null,
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function createReferralBase(
        User $student,
        User $counselor,
        ReportStatus $reportStatus,
        Carbon $createdDate,
    ): array {
        $report = Report::create([
            'user_id'      => $student->id,
            'counselor_id' => $counselor->id,
            'topic'        => 'Indikasi depresi — perlu rujukan psikolog',
            'room'         => 'Ruang BK 1',
            'date'         => $createdDate->toDateString(),
            'time'         => '08:00',
            'status'       => $reportStatus->value,
            'priority'     => 'tinggi',
            'notes'        => 'Setelah evaluasi mendalam, siswa memerlukan pendampingan psikolog profesional.',
            'result'       => null,
            'created_at'   => $createdDate,
            'updated_at'   => $createdDate,
        ]);

        $sharing = Sharing::where('user_id', $student->id)->latest()->first()
            ?? Sharing::create([
                'user_id'     => $student->id,
                'title'       => 'Curhat saya ke BK',
                'description' => 'Saya sudah beberapa waktu merasa tidak bersemangat dan sulit fokus.',
                'reply'       => null,
                'replied_at'  => null,
                'replied_by'  => null,
                'priority'    => 'tinggi',
                'status'      => ReportStatus::DITINJAU->value,
                'created_at'  => $createdDate,
                'updated_at'  => $createdDate,
            ]);

        return [$report, $sharing];
    }

    private function createExternalCounseling(
        User $student,
        User $counselor,
        Report $report,
        ?Sharing $sharing,
        CounselingStatus $status,
        CounselingResolution $resolution,
        ?Carbon $scheduledAt,
    ): Counseling {
        return Counseling::create([
            'source_type'     => 'regular',
            'report_id'       => $report->id,
            'student_id'      => $student->id,
            'counselor_id'    => $counselor->id,
            'sharing_id'      => $sharing?->id,
            'psychologist_id' => $this->psychologistProfile->user_id,
            'room'            => null,
            'notes'           => 'Siswa dirujuk ke psikolog eksternal karena memerlukan penanganan lebih lanjut.',
            'reason'          => 'Resolusi melebihi kapasitas guru BK.',
            'type'            => 'external',
            'method'          => CounselingMethod::OFFLINE->value,
            'status'          => $status->value,
            'resolution'      => $resolution->value,
            'scheduled_at'    => $scheduledAt,
            'cutdown_at'      => $scheduledAt?->copy()->addHour(),
            'created_at'      => now()->subDays(7),
            'updated_at'      => now(),
        ]);
    }

    private function createSlot(
        string $date,
        string $startTime,
        string $endTime,
        SlotStatus $status,
    ): PsychologistSlot {
        return PsychologistSlot::create([
            'psychologist_id' => $this->psychologistProfile->id,
            'slot_date'       => $date,
            'slot_start_time' => $startTime,
            'slot_end_time'   => $endTime,
            'status'          => $status->value,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
