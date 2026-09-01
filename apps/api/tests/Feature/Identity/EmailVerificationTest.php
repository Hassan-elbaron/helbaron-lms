<?php

use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Notifications\EmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('verifies email with the emitted OTP', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Omar',
        'email' => 'omar@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', 'omar@example.com')->firstOrFail();

    $code = null;
    Notification::assertSentTo($user, EmailOtpNotification::class, function ($n) use (&$code) {
        $code = $n->code;

        return true;
    });

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/verify-email', ['code' => $code])->assertOk();
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('rejects a wrong email OTP', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Lina',
        'email' => 'lina@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', 'lina@example.com')->firstOrFail();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/verify-email', ['code' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'AUTH_OTP_INVALID');
});

it('restricts an unverified session to profile, email verification, and logout', function () {
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.email_verified', false);

    $this->putJson('/api/v1/profile', ['name' => 'Not yet'])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'EMAIL_VERIFICATION_REQUIRED');
});

it('allows a verified session to use protected API routes', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')->assertOk();
    $this->putJson('/api/v1/profile', ['name' => 'Verified learner'])->assertOk();
});

/*
 * The gate's coverage, asserted across the surfaces that actually matter.
 *
 * Only PUT /profile was covered before, which proves the middleware runs on ONE route. It is
 * registered on the whole api group, and the things worth proving it stops are the ones that move
 * money, grant access, spend third-party quota, or mint long-lived credentials. A regression that
 * exempted any of them would not have been caught.
 *
 * Every assertion checks the ERROR CODE, not just the status: a plain 403 could equally be an
 * authorization failure, and the distinction is the whole reason the code exists.
 */
it('blocks an unverified session from checkout, enrollment, AI, and developer key issuance', function () {
    Sanctum::actingAs(User::factory()->unverified()->create());

    $blocked = [
        'checkout' => fn () => $this->postJson('/api/v1/checkout', []),
        'enrollment' => fn () => $this->postJson('/api/v1/courses/any-public-id/enroll'),
        'ai tutor' => fn () => $this->postJson('/api/v1/ai/tutor', ['question' => 'hi']),
        'ai copilot' => fn () => $this->postJson('/api/v1/ai/copilot', ['prompt' => 'hi']),
        'developer key issuance' => fn () => $this->postJson('/api/v1/api-keys', ['name' => 'k']),
    ];

    foreach ($blocked as $label => $call) {
        $call()
            ->assertForbidden()
            ->assertJsonPath('error.code', 'EMAIL_VERIFICATION_REQUIRED', "{$label} was not gated");
    }
});

it('keeps the verification surface itself reachable while unverified', function () {
    Notification::fake();
    Sanctum::actingAs(User::factory()->unverified()->create());

    // If any of these were gated the account could never verify or leave — a permanent lockout.
    $this->getJson('/api/v1/profile')->assertOk();
    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    // verify-email reaches its own validation rather than the gate: a 422 proves the middleware let
    // it through, where a 403 EMAIL_VERIFICATION_REQUIRED would prove it did not.
    $this->postJson('/api/v1/auth/verify-email', ['code' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'AUTH_OTP_INVALID');

    $this->postJson('/api/v1/auth/logout')->assertOk();
});
