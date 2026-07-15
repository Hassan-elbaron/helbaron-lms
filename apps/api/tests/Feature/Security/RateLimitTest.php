<?php

use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(fn () => RateLimiter::clear('checkout'));

it('throttles the public certificate verification endpoint', function () {
    // Limiter: certification-verify — 30/min per IP.
    for ($i = 0; $i < 30; $i++) {
        $this->getJson('/api/v1/certificates/verify/NO-SUCH-CODE')->assertStatus(404);
    }

    $this->getJson('/api/v1/certificates/verify/NO-SUCH-CODE')->assertStatus(429);
});

it('throttles checkout per user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Limiter: commerce-checkout — 10/min per user. An empty cart still consumes an attempt.
    $statuses = [];
    for ($i = 0; $i < 11; $i++) {
        $statuses[] = $this->postJson('/api/v1/checkout')->getStatusCode();
    }

    expect(end($statuses))->toBe(429);
});
