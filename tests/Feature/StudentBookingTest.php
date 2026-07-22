<?php

use App\Models\User;
use App\Models\Counseling;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use App\Models\BookingSchedule;
use App\Enums\UserRole;
use App\Enums\SlotStatus;
use App\Enums\BookingStatus;
use App\Enums\CounselingStatus;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('verify student schedule confirmation API endpoints', function () {
    // 1. Setup necessary models
    $school = School::create([
        'name' => 'SMK 1 Test',
        'address' => 'Test Street',
        'phone' => '0211234567',
        'email' => 'smk1@test.com',
        'district' => 'Menteng',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
    ]);

    $student = User::create([
        'name' => 'Student Test',
        'username' => 'student.test',
        'phone' => '08111111111',
        'identifier' => '111111',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
        'verified' => true,
        'school_id' => $school->id,
    ]);

    $counselor = User::create([
        'name' => 'Counselor Test',
        'username' => 'counselor.test',
        'phone' => '08222222222',
        'identifier' => '222222',
        'password' => bcrypt('password'),
        'role' => UserRole::COUNSELOR->value,
        'verified' => true,
        'school_id' => $school->id,
    ]);

    $psychologist = User::create([
        'name' => 'Dr. Sarah Wijaya, M.Psi., Psikolog',
        'username' => 'sarah.wijaya@puskesmas-menteng.id',
        'phone' => '081298765432',
        'identifier' => '333333',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
        'verified' => true,
        'school_id' => $school->id,
    ]);

    $profile = PsychologistProfile::create([
        'user_id' => $psychologist->id,
        'str_number' => 'STR-PSI-00101',
        'specialization' => 'Psikologi Klinis Anak & Remaja',
        'institution_name' => 'Puskesmas Kec. Menteng',
        'phone_number' => '081298765432',
        'is_active' => true,
    ]);

    // Create counseling of type 'external' pointing to $psychologist->id, owned by $student->id
    $counseling = Counseling::create([
        'student_id' => $student->id,
        'counselor_id' => $counselor->id,
        'psychologist_id' => $psychologist->id,
        'type' => 'external',
        'status' => CounselingStatus::DIJADWALKAN->value,
    ]);

    // Seed 4 slots for 2026-07-28
    $slotDate = '2026-07-28';
    $slotsData = [
        ['slot_start_time' => '08:00:00', 'slot_end_time' => '09:00:00'],
        ['slot_start_time' => '09:00:00', 'slot_end_time' => '10:00:00'],
        ['slot_start_time' => '10:00:00', 'slot_end_time' => '11:00:00'],
        ['slot_start_time' => '11:00:00', 'slot_end_time' => '12:00:00'],
    ];

    $slots = [];
    foreach ($slotsData as $sd) {
        $slots[] = PsychologistSlot::create([
            'psychologist_id' => $profile->id,
            'slot_date' => $slotDate,
            'slot_start_time' => $sd['slot_start_time'],
            'slot_end_time' => $sd['slot_end_time'],
            'status' => SlotStatus::AVAILABLE->value,
        ]);
    }

    // Also seed a slot in the future (today + 3 days) to test available-dates
    $futureDate = now()->addDays(3)->toDateString();
    $futureSlot = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => $futureDate,
        'slot_start_time' => '08:00:00',
        'slot_end_time' => '09:00:00',
        'status' => SlotStatus::AVAILABLE->value,
    ]);

    // 2. GET available-dates
    $response = $this->actingAs($student, 'api')
        ->getJson("/api/student/referrals/{$counseling->id}/available-dates");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'psychologist' => ['name', 'facility_name', 'specialization'],
                'earliest_available_date',
                'available_dates',
            ]
        ]);

    // 3. GET available-slots?date=2026-07-28
    $response = $this->actingAs($student, 'api')
        ->getJson("/api/student/referrals/{$counseling->id}/available-slots?date=2026-07-28");

    $response->assertStatus(200)
        ->assertJsonCount(4, 'data.time_slots')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'selected_date',
                'selected_date_formatted',
                'time_slots' => [
                    '*' => ['slot_id', 'time_range', 'is_available']
                ]
            ]
        ]);

    $firstSlot = $slots[0];

    // 4. POST bookings (valid) -> 200, status in DB = tentative, deadline_at ≈ now + 24h
    $response = $this->actingAs($student, 'api')
        ->postJson("/api/student/bookings", [
            'counseling_id' => $counseling->id,
            'slot_id' => $firstSlot->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'booking_id',
                'status',
                'deadline_at',
                'slot' => ['date', 'time_range']
            ]
        ]);

    $bookingId = $response->json('data.booking_id');

    // Assert database values
    $firstSlot->refresh();
    expect($firstSlot->status)->toBe(SlotStatus::TENTATIVE);

    $booking = BookingSchedule::find($bookingId);
    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe(BookingStatus::PENDING);
    expect($booking->deadline_at->diffInHours(now()))->toBeLessThanOrEqual(24);

    // 5. POST bookings with same slot_id -> 409
    $response = $this->actingAs($student, 'api')
        ->postJson("/api/student/bookings", [
            'counseling_id' => $counseling->id,
            'slot_id' => $firstSlot->id,
        ]);

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Slot ini sudah diambil siswa lain. Silakan pilih slot lain.'
        ]);

    // 6. GET bookings/{id} -> 200 with detail
    $response = $this->actingAs($student, 'api')
        ->getJson("/api/student/bookings/{$bookingId}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'booking_id',
                'status',
                'psychologist_name',
                'facility_name',
                'counselor_name',
                'time_slot_label',
                'date_formatted',
                'location',
                'deadline_at',
                'created_at_formatted'
            ]
        ]);
});
