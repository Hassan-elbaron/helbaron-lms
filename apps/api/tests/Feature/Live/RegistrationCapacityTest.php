<?php

use App\Domains\Live\Models\LiveSession;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers within capacity and waitlists beyond it', function () {
    $session = LiveSession::factory()->capacity(1)->create();

    $a = User::factory()->create();
    Sanctum::actingAs($a);
    $this->postJson("/api/v1/live-sessions/{$session->public_id}/register")
        ->assertCreated()->assertJsonPath('data.status', 'registered');

    $b = User::factory()->create();
    Sanctum::actingAs($b);
    $this->postJson("/api/v1/live-sessions/{$session->public_id}/register")
        ->assertCreated()->assertJsonPath('data.status', 'waitlisted');
});

it('rejects registration for a cancelled session', function () {
    $session = LiveSession::factory()->cancelled()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/live-sessions/{$session->public_id}/register")
        ->assertStatus(410)->assertJsonPath('error.code', 'LIVE_SESSION_CANCELLED');
});
