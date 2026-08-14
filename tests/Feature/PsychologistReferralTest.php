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
