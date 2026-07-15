<?php

use App\Domains\Live\Models\LiveSession;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns a waiting-room state before the window and a join token when live', function () {
    // Far-future session -> waiting room.
    $future = LiveSession::factory()->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $future->registrations()->create(['user_id' => $user->id, 'status' => 'registered', 'registered_at' => now()]);

    $this->postJson("/api/v1/live-sessions/{$future->public_id}/join")
        ->assertOk()->assertJsonPath('data.state', 'waiting_room');

    // Live session -> ready with token + join url.
    $live = LiveSession::factory()->live()->create();
    $live->forceFill(['join_url' => 'https://meet.fake.local/room'])->save();
    $live->registrations()->create(['user_id' => $user->id, 'status' => 'registered', 'registered_at' => now()]);

    $res = $this->postJson("/api/v1/live-sessions/{$live->public_id}/join")->assertOk();
    expect($res->json('data.state'))->toBe('ready')
        ->and($res->json('data.token'))->toBeString()
        ->and($res->json('data.join_url'))->toBe('https://meet.fake.local/room');
});

it('records attendance only for registered users', function () {
    $session = LiveSession::factory()->live()->create();
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $this->postJson("/api/v1/live-sessions/{$session->public_id}/attendance")
        ->assertStatus(403)->assertJsonPath('error.code', 'LIVE_NOT_REGISTERED');

    $session->registrations()->create(['user_id' => $user->id, 'status' => 'registered', 'registered_at' => now()]);
    $this->postJson("/api/v1/live-sessions/{$session->public_id}/attendance")->assertOk();

    expect($session->attendances()->where('user_id', $user->id)->count())->toBe(1);
});
