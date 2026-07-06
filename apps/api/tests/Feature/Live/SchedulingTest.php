<?php

use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use App\Domains\Live\Models\LiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);
});

it('schedules a session (Fake meeting) and schedules reminders', function () {
    $res = $this->postJson('/api/v1/admin/live-sessions', [
        'title' => 'Workshop',
        'timezone' => 'Asia/Riyadh',
        'starts_at' => now()->addWeek()->format('Y-m-d 18:00'),
        'duration_minutes' => 90,
        'capacity' => 50,
    ])->assertCreated();

    $session = LiveSession::where('public_id', $res->json('data.id'))->firstOrFail();

    expect($session->status->value)->toBe('scheduled')
        ->and($session->meeting_provider)->toBe('fake')
        ->and($session->reminders()->count())->toBe(2); // offsets [1440, 60]
});

it('reschedules and cancels a session, cancelling reminders', function () {
    $create = $this->postJson('/api/v1/admin/live-sessions', [
        'title' => 'Temp', 'timezone' => 'UTC', 'starts_at' => now()->addWeek()->format('Y-m-d 10:00'), 'duration_minutes' => 60,
    ])->assertCreated();
    $id = $create->json('data.id');

    $this->postJson("/api/v1/admin/live-sessions/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');

    $session = LiveSession::where('public_id', $id)->firstOrFail();
    expect($session->reminders()->where('status', 'pending')->count())->toBe(0);
});
