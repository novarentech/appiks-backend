<?php

use App\Enums\SlotStatus;
use App\Enums\UserRole;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('psychologist slot management endpoints', function () {
    // 1. Setup psychologist user & profile
    $psychologist = User::create([
        'name' => 'Dr. Sarah',
        'username' => 'drsarah',
        'identifier' => 'PSY-001',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
    ]);

    $profile = PsychologistProfile::create([
        'user_id' => $psychologist->id,
        'institution_name' => 'RS Sehat',
        'str_number' => '123456789',
        'specialization' => 'Clinical Psychologist',
    ]);

    // Setup another psychologist for authorization boundary testing
    $otherPsychologist = User::create([
        'name' => 'Dr. John',
        'username' => 'drjohn',
        'identifier' => 'PSY-002',
        'password' => bcrypt('password'),
        'role' => UserRole::PSYCHOLOGIST->value,
    ]);

    PsychologistProfile::create([
        'user_id' => $otherPsychologist->id,
        'institution_name' => 'RS Kasih',
        'str_number' => '987654321',
        'specialization' => 'Child Psychologist',
    ]);

    // Setup student user for non-psychologist authorization test
    $student = User::create([
        'name' => 'Student User',
        'username' => 'student',
        'identifier' => 'STU-001',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
    ]);

    // 2. Non-psychologist access attempt -> 403
    $this->actingAs($student, 'api')
        ->getJson('/api/psychologist/slots')
        ->assertStatus(403);

    // 3. GET /api/psychologist/slots (authenticated psychologist) -> 200
    $this->actingAs($psychologist, 'api')
        ->getJson('/api/psychologist/slots')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);

    // 4. POST with slot_date < today -> 422
    $yesterday = now()->subDay()->toDateString();
    $this->actingAs($psychologist, 'api')
        ->postJson('/api/psychologist/slots', [
            'slot_date' => $yesterday,
            'slot_start_time' => '10:00',
            'slot_end_time' => '11:00',
        ])
        ->assertStatus(422);

    // 5. POST with slot_start_time >= slot_end_time -> 422
    $tomorrow = now()->addDay()->toDateString();
    $this->actingAs($psychologist, 'api')
        ->postJson('/api/psychologist/slots', [
            'slot_date' => $tomorrow,
            'slot_start_time' => '14:00',
            'slot_end_time' => '13:00',
        ])
        ->assertStatus(422);

    // 6. POST valid data -> 201 Created
    $response = $this->actingAs($psychologist, 'api')
        ->postJson('/api/psychologist/slots', [
            'slot_date' => $tomorrow,
            'slot_start_time' => '09:00',
            'slot_end_time' => '10:00',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);

    $slotId = $response->json('data.id');

    // 7. POST overlapping time range -> 409 Conflict
    $this->actingAs($psychologist, 'api')
        ->postJson('/api/psychologist/slots', [
            'slot_date' => $tomorrow,
            'slot_start_time' => '09:30',
            'slot_end_time' => '10:30',
        ])
        ->assertStatus(409);

    // 8. DELETE slot of another psychologist -> 403
    $this->actingAs($otherPsychologist, 'api')
        ->deleteJson("/api/psychologist/slots/{$slotId}")
        ->assertStatus(403);

    // 9. DELETE available slot (owner) -> 200
    $this->actingAs($psychologist, 'api')
        ->deleteJson("/api/psychologist/slots/{$slotId}")
        ->assertStatus(200);

    expect(PsychologistSlot::find($slotId))->toBeNull();

    // 10. DELETE non-available (tentative) slot -> 422
    $nonAvailableSlot = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => $tomorrow,
        'slot_start_time' => '15:00',
        'slot_end_time' => '16:00',
        'status' => SlotStatus::TENTATIVE->value,
    ]);

    $this->actingAs($psychologist, 'api')
        ->deleteJson("/api/psychologist/slots/{$nonAvailableSlot->id}")
        ->assertStatus(422);

    // 11. Test GET /api/psychologist/slots with start and end query parameters
    $date1 = now()->addDays(2)->toDateString();
    $date2 = now()->addDays(5)->toDateString();
    $date3 = now()->addDays(10)->toDateString();

    $slotDate1 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => $date1,
        'slot_start_time' => '08:00',
        'slot_end_time' => '09:00',
        'status' => SlotStatus::AVAILABLE->value,
    ]);

    $slotDate2 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => $date2,
        'slot_start_time' => '08:00',
        'slot_end_time' => '09:00',
        'status' => SlotStatus::AVAILABLE->value,
    ]);

    $slotDate3 = PsychologistSlot::create([
        'psychologist_id' => $profile->id,
        'slot_date' => $date3,
        'slot_start_time' => '08:00',
        'slot_end_time' => '09:00',
        'status' => SlotStatus::AVAILABLE->value,
    ]);

    // Query: ?start=$date2 (should include date2 and date3)
    $this->actingAs($psychologist, 'api')
        ->getJson("/api/psychologist/slots?start={$date2}")
        ->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $slotDate2->id)
        ->assertJsonPath('data.1.id', $slotDate3->id);

    // Query: ?end=$date2 (should include nonAvailableSlot on tomorrow, and slotDate1 on date1, and slotDate2 on date2)
    $this->actingAs($psychologist, 'api')
        ->getJson("/api/psychologist/slots?end={$date2}")
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');

    // Query: ?start=$date1&end=$date2 (should include slotDate1 and slotDate2)
    $this->actingAs($psychologist, 'api')
        ->getJson("/api/psychologist/slots?start={$date1}&end={$date2}")
        ->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $slotDate1->id)
        ->assertJsonPath('data.1.id', $slotDate2->id);
});
