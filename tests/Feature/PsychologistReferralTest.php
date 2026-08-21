<?php

use App\Enums\BookingStatus;
use App\Enums\CounselingStatus;
use App\Enums\SlotStatus;
use App\Enums\UserRole;
use App\Events\BookingConfirmed;
use App\Events\BookingExpired;
use App\Events\BookingRejected;
use App\Models\BookingSchedule;
use App\Models\Counseling;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use App\Models\School;
use App\Models\Sharing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('psychologist pending referrals list, decision, and auto-expiry command', function () {
    Event::fake([BookingConfirmed::class, BookingRejected::class, BookingExpired::class]);

    // 1. Setup Models
    $school = School::create([
        'name' => 'SMK 2 Test',
        'address' => 'Test Street 2',
        'phone' => '02111112222',
        'email' => 'smk2@test.com',
        'district' => 'Menteng',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
    ]);

    $psychologist = User::create([
        'name' => 'Dr. Sarah Referral Test',
        'username' => 'drsarah_reftest',
        'identifier' => 'PSY-900',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
    ]);

    $profile = PsychologistProfile::create([
        'user_id' => $psychologist->id,
        'institution_name' => 'RS Test Referral',
        'str_number' => 'STR-TEST-900',
        'specialization' => 'Clinical Psychologist',
    ]);

    $otherPsychologist = User::create([
        'name' => 'Dr. Other Test',
        'username' => 'drother_test',
        'identifier' => 'PSY-901',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
    ]);

    $otherProfile = PsychologistProfile::create([
        'user_id' => $otherPsychologist->id,
        'institution_name' => 'RS Other',
        'str_number' => 'STR-TEST-901',
        'specialization' => 'Child Psychologist',
    ]);

    $student = User::create([
        'name' => 'Student Ref',
        'username' => 'student_ref_pest',
        'identifier' => 'STU-900',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
        'school_id' => $school->id,
    ]);

    $counselor = User::create([
        'name' => 'Counselor Ref',
        'username' => 'counselor_ref_pest',
        'identifier' => 'COU-900',
        'password' => bcrypt('password'),
        'role' => UserRole::COUNSELOR->value,
        'school_id' => $school->id,
    ]);

    $sharing = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Sharing title',
        'description' => 'Sharing description',
    ]);

    $counseling = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'sharing_id' => $sharing->id,
        'type' => 'external',
        'status' => CounselingStatus::DIJADWALKAN->value,
    ]);

    $slot = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(2)->toDateString(),
        'slot_start_time' => '10:00:00',
        'slot_end_time' => '11:00:00',
        'status' => SlotStatus::TENTATIVE->value,
    ]);

    $booking = BookingSchedule::create([
        'counseling_id' => $counseling->id,
        'slot_id' => $slot->id,
        'student_id' => $student->id,
        'status' => BookingStatus::PENDING->value,
        'deadline_at' => now()->addHours(24),
    ]);

    // 2. GET /api/psychologist/referrals/pending (unauthorized student) -> 403
    $this->actingAs($student, 'api')
        ->getJson('/api/psychologist/referrals/pending')
        ->assertStatus(403);

    // 3. GET /api/psychologist/referrals/pending (psychologist owner) -> 200
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals/pending')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');

    // 4. PATCH decide reject without reject_reason -> 422
    $this->actingAs($psychologist, 'api')
        ->patchJson("/api/psychologist/referrals/{$booking->id}/decide", [
            'action' => 'reject',
        ])
        ->assertStatus(422);

    // 5. PATCH decide from another psychologist -> 403
    $this->actingAs($otherPsychologist, 'api')
        ->patchJson("/api/psychologist/referrals/{$booking->id}/decide", [
            'action' => 'confirm',
        ])
        ->assertStatus(403);

    // 6. PATCH decide confirm -> 200, status updated, slot confirmed, BookingConfirmed dispatched
    $this->actingAs($psychologist, 'api')
        ->patchJson("/api/psychologist/referrals/{$booking->id}/decide", [
            'action' => 'confirm',
        ])
        ->assertStatus(200);

    $booking->refresh();
    $slot->refresh();
    expect($booking->status)->toBe(BookingStatus::CONFIRMED);
    expect($slot->status)->toBe(SlotStatus::CONFIRMED);
    Event::assertDispatched(BookingConfirmed::class);

    // Reset booking & slot for reject test
    $booking->update(['status' => BookingStatus::PENDING->value]);
    $slot->update(['status' => SlotStatus::TENTATIVE->value]);

    // 7. PATCH decide reject with reason -> 200, status rejected, slot available, BookingRejected dispatched
    $this->actingAs($psychologist, 'api')
        ->patchJson("/api/psychologist/referrals/{$booking->id}/decide", [
            'action' => 'reject',
            'reject_reason' => 'Schedule conflict.',
        ])
        ->assertStatus(200);

    $booking->refresh();
    $slot->refresh();
    expect($booking->status)->toBe(BookingStatus::REJECTED);
    expect($booking->reject_reason)->toBe('Schedule conflict.');
    expect($slot->status)->toBe(SlotStatus::AVAILABLE);
    Event::assertDispatched(BookingRejected::class);

    // 8. Console Command: referrals:expire-pending
    $expiredSlot = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(2)->toDateString(),
        'slot_start_time' => '14:00:00',
        'slot_end_time' => '15:00:00',
        'status' => SlotStatus::TENTATIVE->value,
    ]);

    $expiredBooking = BookingSchedule::create([
        'counseling_id' => $counseling->id,
        'slot_id' => $expiredSlot->id,
        'student_id' => $student->id,
        'status' => BookingStatus::PENDING->value,
        'deadline_at' => now()->subHour(),
    ]);

    $this->artisan('referrals:expire-pending')
        ->expectsOutput('Successfully expired 1 pending referrals.')
        ->assertExitCode(0);

    $expiredBooking->refresh();
    $expiredSlot->refresh();
    expect($expiredBooking->status)->toBe(BookingStatus::EXPIRED);
    expect($expiredSlot->status)->toBe(SlotStatus::AVAILABLE);
    Event::assertDispatched(BookingExpired::class);
});

test('psychologist referrals overview and paginated list with filters', function () {
    \Illuminate\Support\Facades\Queue::fake([\App\Jobs\ProcessNlpAnalysisJob::class]);

    $school = School::create([
        'name' => 'SMK 3 Overview Test',
        'address' => 'Test Street 3',
        'phone' => '02133334444',
        'email' => 'smk3@test.com',
        'district' => 'Menteng',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
    ]);

    $psychologist = User::create([
        'name' => 'Dr. Overview Psychologist',
        'username' => 'dr_overview_psy',
        'identifier' => 'PSY-700',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
    ]);

    $profile = PsychologistProfile::create([
        'user_id' => $psychologist->id,
        'institution_name' => 'RS Jiwa Sehat',
        'str_number' => 'STR-TEST-700',
        'specialization' => 'Clinical Psychologist',
    ]);

    $student = User::create([
        'name' => 'Student Overview',
        'username' => 'student_overview',
        'identifier' => 'STU-700',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
        'school_id' => $school->id,
    ]);

    $counselor = User::create([
        'name' => 'Counselor Overview',
        'username' => 'counselor_overview',
        'identifier' => 'COU-700',
        'password' => bcrypt('password'),
        'role' => UserRole::COUNSELOR->value,
        'school_id' => $school->id,
    ]);

    // 1. Pending referral (Priority: tinggi / kritis, Batas waktu: aktif)
    $sharing1 = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Curhat 1',
        'description' => 'Desc 1',
        'priority' => 'tinggi',
    ]);
    $counseling1 = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'sharing_id' => $sharing1->id,
        'type' => 'external',
        'status' => CounselingStatus::DIJADWALKAN->value,
    ]);
    $slot1 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(1)->toDateString(),
        'slot_start_time' => '09:00:00',
        'slot_end_time' => '10:00:00',
        'status' => SlotStatus::TENTATIVE->value,
    ]);
    $booking1 = BookingSchedule::create([
        'counseling_id' => $counseling1->id,
        'slot_id' => $slot1->id,
        'student_id' => $student->id,
        'status' => BookingStatus::PENDING->value,
        'deadline_at' => now()->addHours(12),
    ]);

    // 2. Confirmed referral (Priority: rendah / prioritas, Counseling != selesai)
    $sharing2 = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Curhat 2',
        'description' => 'Desc 2',
        'priority' => 'rendah',
    ]);
    $counseling2 = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'sharing_id' => $sharing2->id,
        'type' => 'external',
        'status' => CounselingStatus::DIJADWALKAN->value,
    ]);
    $slot2 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(2)->toDateString(),
        'slot_start_time' => '10:00:00',
        'slot_end_time' => '11:00:00',
        'status' => SlotStatus::CONFIRMED->value,
    ]);
    $booking2 = BookingSchedule::create([
        'counseling_id' => $counseling2->id,
        'slot_id' => $slot2->id,
        'student_id' => $student->id,
        'status' => BookingStatus::CONFIRMED->value,
        'deadline_at' => now()->subHours(2),
    ]);

    // 3. Selesai referral (Priority: tinggi / kritis, Counseling == selesai)
    $sharing3 = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Curhat 3',
        'description' => 'Desc 3',
        'priority' => 'tinggi',
    ]);
    $counseling3 = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'sharing_id' => $sharing3->id,
        'type' => 'external',
        'status' => CounselingStatus::SELESAI->value,
    ]);
    $slot3 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->subDays(1)->toDateString(),
        'slot_start_time' => '11:00:00',
        'slot_end_time' => '12:00:00',
        'status' => SlotStatus::CONFIRMED->value,
    ]);
    $booking3 = BookingSchedule::create([
        'counseling_id' => $counseling3->id,
        'slot_id' => $slot3->id,
        'student_id' => $student->id,
        'status' => BookingStatus::CONFIRMED->value,
        'deadline_at' => now()->subDay(),
    ]);

    // 4. Rejected referral
    $sharing4 = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Curhat 4',
        'description' => 'Desc 4',
        'priority' => 'rendah',
    ]);
    $counseling4 = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'sharing_id' => $sharing4->id,
        'type' => 'external',
        'status' => CounselingStatus::DITOLAK->value,
    ]);
    $slot4 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(3)->toDateString(),
        'slot_start_time' => '13:00:00',
        'slot_end_time' => '14:00:00',
        'status' => SlotStatus::AVAILABLE->value,
    ]);
    $booking4 = BookingSchedule::create([
        'counseling_id' => $counseling4->id,
        'slot_id' => $slot4->id,
        'student_id' => $student->id,
        'status' => BookingStatus::REJECTED->value,
        'reject_reason' => 'Busy',
        'deadline_at' => now()->subHours(5),
    ]);

    // 5. Expired referral
    $sharing5 = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Curhat 5',
        'description' => 'Desc 5',
        'priority' => 'rendah',
    ]);
    $counseling5 = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'sharing_id' => $sharing5->id,
        'type' => 'external',
        'status' => CounselingStatus::DIJADWALKAN->value,
    ]);
    $slot5 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => now()->addDays(4)->toDateString(),
        'slot_start_time' => '14:00:00',
        'slot_end_time' => '15:00:00',
        'status' => SlotStatus::AVAILABLE->value,
    ]);
    $booking5 = BookingSchedule::create([
        'counseling_id' => $counseling5->id,
        'slot_id' => $slot5->id,
        'student_id' => $student->id,
        'status' => BookingStatus::EXPIRED->value,
        'deadline_at' => now()->subHours(10),
    ]);

    // A. Test GET /api/psychologist/referrals-overview
    // Unauthorized student -> 403
    $this->actingAs($student, 'api')
        ->getJson('/api/psychologist/referrals-overview')
        ->assertStatus(403);

    // Authorized psychologist -> 200
    $overviewResponse = $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals-overview')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pending', 1)
        ->assertJsonPath('data.confirmed', 1)
        ->assertJsonPath('data.selesai', 1);

    expect(array_key_exists('total', $overviewResponse->json('data')))->toBeFalse();

    // B. Test GET /api/psychologist/referrals (all + pagination)
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?per_page=2')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.data')
        ->assertJsonPath('data.meta.total', 5);

    // Filter: status = menunggu konfirmasi
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?status=menunggu+konfirmasi')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.status', 'pending');

    // Filter: status = terkonfirmasi
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?status=terkonfirmasi')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $booking2->id);

    // Filter: status = selesai
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?status=selesai')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $booking3->id);

    // Filter: status = ditolak
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?status=ditolak')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.status', 'rejected');

    // Filter: status = kadaluarsa
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?status=kadaluarsa')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.status', 'expired');

    // Filter: priority = kritis
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?priority=kritis')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data.data');

    // Filter: priority = prioritas
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?priority=prioritas')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data.data');

    // Filter: batas_waktu = aktif
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?batas_waktu=aktif')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data.data'); // booking1 (deadline future), booking2 & booking3 (status confirmed)

    // Filter: batas_waktu = kadaluarsa
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?batas_waktu=kadaluarsa')
        ->assertStatus(200)
        ->assertJsonCount(4, 'data.data'); // booking2, booking3, booking4, booking5 (deadlines in the past)

    // Filter: search = Student (matches 'Student Overview')
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?search=Student')
        ->assertStatus(200)
        ->assertJsonPath('data.meta.total', 5);

    // Filter: search = NonExistent
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/referrals?search=NonExistent')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.data');
});
