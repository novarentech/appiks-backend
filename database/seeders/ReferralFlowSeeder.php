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
 * Seeds all 5 referral scenarios for the psychologist booking flow (AND-2).
 *
 * Prerequisites: PsychologistSeeder + PsychologistSlotSeeder must have run first.
 *
 *   A) Full happy path  — consent granted → booking confirmed → session complete
 *   B) Consent pending  — waiting for student to respond
 *   C) Consent rejected — student refused data sharing
 *   D) Booking rejected — psychologist declined the request
 *   E) Booking expired  — student never picked a slot in time
 */
class ReferralFlowSeeder extends Seeder
{
    private PsychologistProfile $psychologistProfile;

    public function run(): void
    {
        $psychUser = User::where('role', 'psychologist')->first();

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

        if ($students->count() < 5) {
            $this->command->warn('ReferralFlowSeeder: At least 5 verified students are required.');
            return;
        }

        $this->seedScenarioA($students[0], $counselor);
        $this->seedScenarioB($students[1], $counselor);
        $this->seedScenarioC($students[2], $counselor);
        $this->seedScenarioD($students[3], $counselor);
        $this->seedScenarioE($students[4], $counselor);

        if (isset($students[5])) {
            $this->seedScenarioF($students[5], $counselor);
        }

        $this->command->info('ReferralFlowSeeder: All referral scenarios seeded successfully.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario A — Happy path: consent granted → booking confirmed → completed
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioA(User $student, User $counselor): void
    {
        $sessionDate = Carbon::now()->subDays(3);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::SELESAI, $sessionDate->copy()->subDays(10));

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::SELESAI,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: $sessionDate,
        );

        // Consent: granted
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => json_encode(['data_sharing', 'clinical_notes', 'session_recording']),
            'granted_at'    => $sessionDate->copy()->subDays(2),
            'rejected_at'   => null,
            'created_at'    => $sessionDate->copy()->subDays(5),
            'updated_at'    => $sessionDate->copy()->subDays(2),
        ]);

        // Slot: confirmed (create a dedicated slot for this booking)
        $slot = $this->createSlot(
            date: $sessionDate->toDateString(),
            startTime: '09:00:00',
            endTime: '10:00:00',
            status: SlotStatus::CONFIRMED,
        );

        // Booking: confirmed
        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::CONFIRMED->value,
            'deadline_at'   => $sessionDate->copy()->subDays(1)->addHours(24),
            'location'      => 'Puskesmas Kec. Menteng, Lt. 2, Ruang Konseling',
            'created_at'    => $sessionDate->copy()->subDays(2),
            'updated_at'    => $sessionDate->copy()->subDays(1),
        ]);

        // Completed session artifacts
        CounselingLog::create([
            'counseling_id'     => $counseling->id,
            'student_id'        => $student->id,
            'counselor_id'      => $counselor->id,
            'session_mode'      => CounselingMethod::OFFLINE->value,
            'clinical_notes'    => 'Sesi berjalan lancar dengan psikolog. Siswa menunjukkan tanda kemajuan positif. Direkomendasikan sesi lanjutan 2x sebulan.',
            'resolution_status' => CounselingResolution::NEEDMORE->value,
            'created_at'        => $sessionDate,
            'updated_at'        => $sessionDate,
        ]);

        ClinicalSummary::create([
            'counseling_id' => $counseling->id,
            'summary_data'  => json_encode([
                'chief_complaint'  => 'Gejala depresi ringan-sedang yang tidak tertangani di sekolah.',
                'assessment'       => 'Memerlukan pendampingan psikolog profesional secara berkala.',
                'plan'             => 'Sesi 2x/bulan dengan Dr. Sarah. Koordinasi dengan guru BK.',
                'session_count'    => 1,
                'resolution'       => CounselingResolution::NEEDMORE->value,
                'psychologist'     => 'Dr. Sarah Wijaya, M.Psi., Psikolog',
            ]),
            'raw_payload'   => [
                'mood_history'    => [],
                'sharing_history' => [
                    ['title' => 'Curhat saya ke BK', 'priority' => 'tinggi']
                ],
                'assesment_logs'  => [],
            ],
            'created_at' => $sessionDate,
            'updated_at' => $sessionDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario B — Consent pending (student has not yet responded)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioB(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(1);

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

        // Consent: pending
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::PENDING->value,
            'scopes'        => json_encode(['data_sharing', 'clinical_notes']),
            'granted_at'    => null,
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario C — Consent rejected by student
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioC(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(5);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::DIBATALKAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DITOLAK,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: null,
        );

        // Consent: rejected
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::REJECTED->value,
            'scopes'        => null,
            'granted_at'    => null,
            'rejected_at'   => $createdDate->copy()->addDay(),
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addDay(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario D — Booking rejected by psychologist
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioD(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(4);

        [$report, $sharing] = $this->createReferralBase($student, $counselor, ReportStatus::DIJADWALKAN, $createdDate);

        $counseling = $this->createExternalCounseling(
            student: $student,
            counselor: $counselor,
            report: $report,
            sharing: $sharing,
            status: CounselingStatus::DIJADWALKAN,
            resolution: CounselingResolution::NEEDMORE,
            scheduledAt: Carbon::now()->addDays(5),
        );

        // Consent: granted first
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => json_encode(['data_sharing', 'clinical_notes']),
            'granted_at'    => $createdDate->copy()->addDay(),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addDay(),
        ]);

        // Slot: still available (psychologist rejected, slot freed up)
        $slot = $this->createSlot(
            date: Carbon::now()->addDays(5)->toDateString(),
            startTime: '13:00:00',
            endTime: '14:00:00',
            status: SlotStatus::AVAILABLE,
        );

        // Booking: rejected
        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::REJECTED->value,
            'deadline_at'   => $createdDate->copy()->addDays(2),
            'location'      => 'Puskesmas Kec. Menteng',
            'created_at'    => $createdDate->copy()->addDay(),
            'updated_at'    => $createdDate->copy()->addDays(2),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario E — Booking expired (student never confirmed within SLA)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioE(User $student, User $counselor): void
    {
        $createdDate = Carbon::now()->subDays(8);

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

        // Consent: granted
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => json_encode(['data_sharing']),
            'granted_at'    => $createdDate->copy()->addDay(),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addDay(),
        ]);

        // Slot: available (expired bookings should not lock the slot)
        $slot = $this->createSlot(
            date: Carbon::now()->addDays(2)->toDateString(),
            startTime: '10:00:00',
            endTime: '11:00:00',
            status: SlotStatus::AVAILABLE,
        );

        // Booking: expired (deadline_at is in the past)
        BookingSchedule::create([
            'counseling_id' => $counseling->id,
            'slot_id'       => $slot->id,
            'student_id'    => $student->id,
            'status'        => BookingStatus::EXPIRED->value,
            'deadline_at'   => $createdDate->copy()->addDays(2), // already past
            'location'      => null,
            'created_at'    => $createdDate->copy()->addDay(),
            'updated_at'    => $createdDate->copy()->addDays(2),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scenario F — Booking pending psychologist confirmation (Active SLA)
    // ────────────────────────────────────────────────────────────────────────
    private function seedScenarioF(User $student, User $counselor): void
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
            scheduledAt: Carbon::now()->addDays(3),
        );

        // Consent: granted
        CounselingConsent::create([
            'counseling_id' => $counseling->id,
            'status'        => ConsentStatus::GRANTED->value,
            'scopes'        => json_encode(['mood_history', 'sharing_history', 'assesment_logs']),
            'granted_at'    => $createdDate->copy()->addHour(),
            'rejected_at'   => null,
            'created_at'    => $createdDate,
            'updated_at'    => $createdDate->copy()->addHour(),
        ]);

        // Slot: tentative
        $slot = $this->createSlot(
            date: Carbon::now()->addDays(3)->toDateString(),
            startTime: '14:00:00',
            endTime: '15:00:00',
            status: SlotStatus::TENTATIVE,
        );

        // Booking: pending confirmation (active 24h deadline)
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
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Create the base Report + Sharing that drives a referral counseling.
     *
     * @return array{0: Report, 1: Sharing}
     */
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

    /**
     * Create an external counseling record with NEEDMORE resolution.
     */
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
            'source_type'    => 'regular',
            'report_id'      => $report->id,
            'student_id'     => $student->id,
            'counselor_id'   => $counselor->id,
            'sharing_id'     => $sharing?->id,
            'psychologist_id' => $this->psychologistProfile->user_id,
            'room'           => null,
            'notes'          => 'Siswa dirujuk ke psikolog eksternal karena memerlukan penanganan lebih lanjut.',
            'reason'         => 'Resolusi melebihi kapasitas guru BK.',
            'type'           => 'external',
            'method'         => CounselingMethod::OFFLINE->value,
            'status'         => $status->value,
            'resolution'     => $resolution->value,
            'scheduled_at'   => $scheduledAt,
            'cutdown_at'     => $scheduledAt?->copy()->addHour(),
            'created_at'     => now()->subDays(7),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Create a psychologist slot for booking scenarios.
     */
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
