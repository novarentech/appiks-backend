<?php

use App\Enums\BookingStatus;
use App\Enums\ConsentStatus;
use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use App\Enums\SlotStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateGeminiReferralSummaryJob;
use App\Models\BookingSchedule;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
use App\Models\CounselingConsent;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use App\Models\Room;
use App\Models\School;
use App\Models\Sharing;
use App\Models\User;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('student consent triggers gemini summary job and psychologist can fetch summary report', function () {
    // 1. Setup Models
    $school = School::create([
        'name' => 'SMK 1 Test',
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '0211234567',
        'email' => 'smk1@test.com',
        'district' => 'Gambir',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
    ]);

    $room = Room::create([
        'name' => 'XII RPL 1',
        'code' => 'RPL-1',
        'level' => 'XII',
        'school_id' => $school->id,
    ]);

    $student = User::create([
        'name' => 'Budi Siswa',
        'username' => 'budi_siswa',
        'identifier' => 'SIS-101',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
        'school_id' => $school->id,
        'room_id' => $room->id,
        'verified' => true,
    ]);

    $counselor = User::create([
        'name' => 'Guru BK Test',
        'username' => 'gurubk_test',
        'identifier' => 'COU-101',
        'password' => bcrypt('password'),
        'role' => UserRole::COUNSELOR->value,
        'school_id' => $school->id,
        'verified' => true,
    ]);

    $psychologist = User::create([
        'name' => 'Dr. Maya Psikolog',
        'username' => 'drmaya_psikolog',
        'identifier' => 'PSY-101',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
        'verified' => true,
    ]);

    $profile = PsychologistProfile::create([
        'user_id' => $psychologist->id,
        'institution_name' => 'Klinik Sehat Jiwa',
        'str_number' => 'STR-PSY-101',
        'specialization' => 'Clinical Psychologist',
    ]);

    $otherPsychologist = User::create([
        'name' => 'Dr. Other Psikolog',
        'username' => 'drother_psikolog',
        'identifier' => 'PSY-102',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
        'verified' => true,
    ]);

    PsychologistProfile::create([
        'user_id' => $otherPsychologist->id,
        'institution_name' => 'Klinik Lain',
        'str_number' => 'STR-PSY-102',
        'specialization' => 'Child Psychologist',
    ]);

    $sharing = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Tekanan Belajar',
        'description' => 'Sering merasa cemas dan panik saat menjelang ujian akhir.',
        'priority' => 'tinggi',
    ]);

    $mood = \App\Models\MoodRecord::create([
        'user_id' => $student->id,
        'recorded' => now()->subDays(2)->toDateString(),
        'status' => \App\Enums\MoodStatus::SAD->value,
    ]);

    $counseling = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'sharing_id' => $sharing->id,
        'type' => 'external',
        'resolution' => CounselingResolution::NEEDMORE->value,
        'status' => CounselingStatus::DIJADWALKAN->value,
    ]);

    $counselingLog = \App\Models\CounselingLog::create([
        'counseling_id' => $counseling->id,
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'session_mode' => 'offline',
        'clinical_notes' => 'Siswa memerlukan pendampingan lebih lanjut oleh psikolog.',
        'resolution_status' => CounselingResolution::NEEDMORE->value,
    ]);

    $consent = CounselingConsent::create([
        'counseling_id' => $counseling->id,
        'status' => ConsentStatus::PENDING->value,
    ]);

    $slot = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(2)->toDateString(),
        'slot_start_time' => '09:00:00',
        'slot_end_time' => '10:00:00',
        'status' => SlotStatus::CONFIRMED->value,
    ]);

    $booking = BookingSchedule::create([
        'counseling_id' => $counseling->id,
        'slot_id' => $slot->id,
        'student_id' => $student->id,
        'status' => BookingStatus::CONFIRMED->value,
        'deadline_at' => now()->addHours(24),
    ]);

    // 2. Test Consent PATCH triggers GenerateGeminiReferralSummaryJob
    Queue::fake();

    $this->actingAs($student, 'api')
        ->patchJson("/api/student/consents/{$consent->id}", [
            'is_granted' => true,
            'scopes' => ['mood_history', 'sharing_history', 'assesment_logs'],
        ])
        ->assertStatus(200);

    $consent->refresh();
    expect($consent->status)->toBe(ConsentStatus::GRANTED);
    expect($consent->scopes)->toEqual(['mood_history', 'sharing_history', 'assesment_logs']);
    Queue::assertPushed(GenerateGeminiReferralSummaryJob::class);

    // 3. Test GenerateGeminiReferralSummaryJob execution with Gemini Fake
    config(['gemini.api_key' => 'fake-api-key']);
    Gemini::fake([
        GenerateContentResponse::fake([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Siswa menunjukkan kecemasan terkait ujian akademik. Rekomendasi: lakukan teknik relaksasi sebelum ujian.',
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    // Run job synchronously
    $job = new GenerateGeminiReferralSummaryJob($counseling);
    $job->handle();

    $summary = ClinicalSummary::where('counseling_id', $counseling->id)->first();
    expect($summary)->not->toBeNull();
    expect($summary->summary_data)->toContain('Siswa menunjukkan kecemasan terkait ujian akademik');
    expect($summary->raw_payload)->toBeArray();
    expect(array_key_exists('mood_history', $summary->raw_payload))->toBeTrue();
    expect(array_key_exists('sharing_history', $summary->raw_payload))->toBeTrue();
    expect(array_key_exists('assesment_logs', $summary->raw_payload))->toBeTrue();

    // 4. Test GET /api/psychologist/referrals/{counseling}/summary Authorization
    // 4a. Unauthorized Student -> 403
    $this->actingAs($student, 'api')
        ->getJson("/api/psychologist/referrals/{$counseling->id}/summary")
        ->assertStatus(403);

    // 4b. Different Psychologist (not assigned) -> 403
    $this->actingAs($otherPsychologist, 'api')
        ->getJson("/api/psychologist/referrals/{$counseling->id}/summary")
        ->assertStatus(403);

    // 4c. Assigned Psychologist -> 200 OK
    $response = $this->actingAs($psychologist, 'api')
        ->getJson("/api/psychologist/referrals/{$counseling->id}/summary")
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.student.name', 'Budi Siswa')
        ->assertJsonPath('data.student.nis', 'budi_siswa')
        ->assertJsonPath('data.student.class', 'XII RPL 1')
        ->assertJsonPath('data.llm_provider', 'gemini-2.5-flash')
        ->assertJsonPath('data.summary_text', $summary->summary_data);

    $responseData = $response->json('data');
    expect(array_key_exists('student', $responseData))->toBeTrue();
    expect(array_key_exists('summary_text', $responseData))->toBeTrue();
    expect(array_key_exists('raw_payload', $responseData))->toBeTrue();
    expect(array_key_exists('generated_at', $responseData))->toBeTrue();
    expect(array_key_exists('llm_provider', $responseData))->toBeTrue();
    expect($responseData['clinical_notes'])->toBeNull();
    expect($responseData['rating'])->toBeNull();
    expect($responseData['improvement_feedback'])->toBeNull();

    // 5. Test POST /api/psychologist/referrals/{counseling}/feedback
    // 5a. Unauthorized student -> 403
    $this->actingAs($student, 'api')
        ->postJson("/api/psychologist/referrals/{$counseling->id}/feedback", [
            'clinical_notes' => 'Catatan tes',
        ])
        ->assertStatus(403);

    // 5b. Unauthorized other psychologist -> 403
    $this->actingAs($otherPsychologist, 'api')
        ->postJson("/api/psychologist/referrals/{$counseling->id}/feedback", [
            'clinical_notes' => 'Catatan tes',
        ])
        ->assertStatus(403);

    // 5c. Validation error: rating must be good or bad -> 422
    $this->actingAs($psychologist, 'api')
        ->postJson("/api/psychologist/referrals/{$counseling->id}/feedback", [
            'rating' => 'invalid_rating',
        ])
        ->assertStatus(422);

    // 5d. Valid feedback submission -> 200
    $this->actingAs($psychologist, 'api')
        ->postJson("/api/psychologist/referrals/{$counseling->id}/feedback", [
            'clinical_notes' => 'Pasien menunjukkan kecemasan situasional yang dipicu oleh tekanan akademik.',
            'rating' => 'good',
            'improvement_feedback' => 'Ringkasan akurat dan sangat membantu.',
        ])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.clinical_notes', 'Pasien menunjukkan kecemasan situasional yang dipicu oleh tekanan akademik.')
        ->assertJsonPath('data.rating', 'good')
        ->assertJsonPath('data.improvement_feedback', 'Ringkasan akurat dan sangat membantu.');

    // 5e. Re-fetch summary to verify persisted clinical notes & feedback
    $this->actingAs($psychologist, 'api')
        ->getJson("/api/psychologist/referrals/{$counseling->id}/summary")
        ->assertStatus(200)
        ->assertJsonPath('data.clinical_notes', 'Pasien menunjukkan kecemasan situasional yang dipicu oleh tekanan akademik.')
        ->assertJsonPath('data.rating', 'good')
        ->assertJsonPath('data.improvement_feedback', 'Ringkasan akurat dan sangat membantu.');
});
