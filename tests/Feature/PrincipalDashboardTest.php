<?php

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Jobs\ZoneNotificationDispatcher;
use App\Models\School;
use App\Models\Sharing;
use App\Models\User;
use App\Notifications\RedZoneAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('principal dashboard and notifications full workflow', function () {
    // 1. Setup School & Users
    $school = School::create([
        'name' => 'SMK 1 Test',
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '0211234567',
        'email' => 'smk1@test.com',
        'district' => 'Gambir',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
    ]);

    $headteacher = User::create([
        'name' => 'Kepala Sekolah Test',
        'username' => 'headteacher_test',
        'identifier' => 'KEP-001',
        'password' => bcrypt('password'),
        'role' => UserRole::HEADTEACHER->value,
        'school_id' => $school->id,
        'verified' => true,
    ]);

    $counselor = User::create([
        'name' => 'Guru BK Test',
        'username' => 'gurubk_test',
        'identifier' => 'COU-001',
        'password' => bcrypt('password'),
        'role' => UserRole::COUNSELOR->value,
        'school_id' => $school->id,
        'verified' => true,
    ]);

    $student = User::create([
        'name' => 'Siswa Test',
        'username' => 'siswa_test',
        'identifier' => 'SIS-001',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
        'school_id' => $school->id,
        'counselor_id' => $counselor->id,
        'verified' => true,
    ]);

    $otherSchool = School::create([
        'name' => 'SMK 2 Test',
        'address' => 'Jl. Thamrin No. 2',
        'phone' => '0217654321',
        'email' => 'smk2@test.com',
        'district' => 'Menteng',
        'city' => 'Jakarta Pusat',
        'province' => 'DKI Jakarta',
    ]);

    $otherSchoolStudent = User::create([
        'name' => 'Siswa Luar',
        'username' => 'siswa_luar',
        'identifier' => 'SIS-999',
        'password' => bcrypt('password'),
        'role' => UserRole::STUDENT->value,
        'school_id' => $otherSchool->id,
        'verified' => true,
    ]);

    // 2. Create Incidents / Sharings
    $activeIncident = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Sensitive Student Title',
        'description' => 'Highly confidential student confession',
        'reply' => 'Private counselor response',
    ]);
    $activeIncident->update([
        'priority' => 'tinggi',
        'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
        'acknowledged_at' => null,
    ]);

    $breachedIncident = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Another Sensitive Title',
        'description' => 'Confidential note from student',
    ]);
    $breachedIncident->update([
        'priority' => 'tinggi',
        'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
        'created_at' => now()->subHours(25),
        'acknowledged_at' => null,
    ]);

    $resolvedIncident = Sharing::create([
        'user_id' => $student->id,
        'title' => 'Resolved issue',
        'description' => 'Resolved description',
    ]);
    $resolvedIncident->update([
        'priority' => 'tinggi',
        'status' => ReportStatus::SELESAI->value,
        'acknowledged_at' => now()->subHours(5),
    ]);

    // Outside school incident (should be ignored)
    $otherSchoolIncident = Sharing::create([
        'user_id' => $otherSchoolStudent->id,
        'title' => 'Other school issue',
        'description' => 'Other school description',
    ]);
    $otherSchoolIncident->update([
        'priority' => 'tinggi',
        'status' => ReportStatus::MENUNGGU_TINJAUAN->value,
    ]);

    // 3. RBAC Check: Student cannot access headteacher dashboard
    $this->actingAs($student, 'api')
        ->getJson('/api/headteacher/dashboard/stats')
        ->assertStatus(403);

    // 4. GET /api/headteacher/dashboard/stats as Headteacher -> 200
    $statsResponse = $this->actingAs($headteacher, 'api')
        ->getJson('/api/headteacher/dashboard/stats');
    $statsResponse->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.active_cases', 2)
        ->assertJsonPath('data.resolved_interventions', 1);

    // 5. GET /api/headteacher/incidents as Headteacher -> 200
    $incidentsResponse = $this->actingAs($headteacher, 'api')
        ->getJson('/api/headteacher/incidents')
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $incidentsData = $incidentsResponse->json('data');
    expect(count($incidentsData))->toBe(3);

    foreach ($incidentsData as $incident) {
        // Strictly masked: MUST NOT have title, description, reply
        expect(array_key_exists('title', $incident))->toBeFalse();
        expect(array_key_exists('description', $incident))->toBeFalse();
        expect(array_key_exists('reply', $incident))->toBeFalse();

        // Metadata fields MUST be present
        expect(array_key_exists('id', $incident))->toBeTrue();
        expect(array_key_exists('status', $incident))->toBeTrue();
        expect(array_key_exists('priority', $incident))->toBeTrue();
        expect(array_key_exists('created_at', $incident))->toBeTrue();
        expect(array_key_exists('acknowledged_at', $incident))->toBeTrue();
        expect(array_key_exists('assigned_counselor', $incident))->toBeTrue();
        expect(array_key_exists('is_sla_breached', $incident))->toBeTrue();
    }

    $incidentMap = collect($incidentsData)->keyBy('id');
    expect($incidentMap[$activeIncident->id]['is_sla_breached'])->toBeFalse();
    expect($incidentMap[$breachedIncident->id]['is_sla_breached'])->toBeTrue();
    expect($incidentMap[$resolvedIncident->id]['is_sla_breached'])->toBeFalse();
    expect($incidentMap[$activeIncident->id]['assigned_counselor'])->toBe('Guru BK Test');

    // 6. PATCH /api/sharing/acknowledge/{id} as Guru BK -> verify acknowledged_at is updated
    expect($activeIncident->acknowledged_at)->toBeNull();
    $this->actingAs($counselor, 'api')
        ->patchJson("/api/sharing/acknowledge/{$activeIncident->id}")
        ->assertStatus(200);

    $activeIncident->refresh();
    expect($activeIncident->acknowledged_at)->not->toBeNull();
    expect($activeIncident->status)->toBe(ReportStatus::DITINJAU->value);

    // 7. Test ZoneNotificationDispatcher & Database Notifications
    ZoneNotificationDispatcher::dispatchSync($activeIncident);

    expect($counselor->notifications()->count())->toBe(1);
    expect($headteacher->notifications()->count())->toBe(1);

    // 8. Test Database Notification Mark As Read
    $notification = $headteacher->notifications()->first();
    expect($notification)->not->toBeNull();
    expect($notification->read_at)->toBeNull();

    // PATCH /api/headteacher/notifications/{id}/read
    $this->actingAs($headteacher, 'api')
        ->patchJson("/api/headteacher/notifications/{$notification->id}/read")
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});
