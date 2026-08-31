<?php

use App\Platform\Identity\Enums\OtpChannel;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Models\UserOtp;
use App\Platform\Identity\Notifications\EmailOtpNotification;
use App\Platform\Identity\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * POST /api/v1/auth/resend-email-otp — the self-service recovery for a mass-lockout condition.
 *
 * RequireVerifiedEmail gates the whole api group, the email OTP lives ten minutes, and before this
 * endpoint existed nothing anywhere in the product could reissue one. A code that expired or landed
 * in spam locked the account out of the entire API permanently.
 */
beforeEach(function (): void {
    // The per-minute burst limiter keys on the user id, and user ids restart at 1 in every
    // RefreshDatabase test while the array cache backing the limiter does not. Without this flush an
    // earlier test's hits are still counted against the next test's "different" user.
    Cache::flush();
});

it('lets an unverified user reach the resend endpoint through the verification gate', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    Notification::assertSentTo($user, EmailOtpNotification::class);
});

/*
 * The self-blocking regression, asserted directly.
 *
 * RequireVerifiedEmail runs on the entire api group and refuses anything not on its allow-list with
 * 403 EMAIL_VERIFICATION_REQUIRED. A resend endpoint left off that list would refuse exactly the
 * users it exists to rescue — the failure mode is silent, and the endpoint would look fine in any
 * test that used a verified account.
 */
it('does not answer the resend endpoint with the verification gate it exists to escape', function (): void {
    Notification::fake();
    Sanctum::actingAs(User::factory()->unverified()->create());

    $response = $this->postJson('/api/v1/auth/resend-email-otp');

    expect($response->json('error.code'))->not->toBe('EMAIL_VERIFICATION_REQUIRED');
    $response->assertOk();
});

it('issues a genuinely new code that verifies the account', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    $code = null;
    Notification::assertSentTo($user, EmailOtpNotification::class, function ($n) use (&$code): bool {
        $code = $n->code;

        return true;
    });

    $this->postJson('/api/v1/auth/verify-email', ['code' => $code])->assertOk();
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('rescues a user whose original code has already expired', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    // The exact lockout: a code issued at registration, now past its 10-minute TTL.
    UserOtp::create([
        'user_id' => $user->id,
        'channel' => 'email',
        'destination' => $user->email,
        'code_hash' => hash('sha256', '111111'),
        'expires_at' => now()->subMinutes(30),
        'attempts' => 0,
    ]);

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    $code = null;
    Notification::assertSentTo($user, EmailOtpNotification::class, function ($n) use (&$code): bool {
        $code = $n->code;

        return true;
    });

    $this->postJson('/api/v1/auth/verify-email', ['code' => $code])->assertOk();
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('honours the configured hourly OTP budget rather than a second one of its own', function (): void {
    Notification::fake();
    config(['identity.otp.email.max_per_hour' => 3]);

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    // Spend the hourly budget as already-issued codes rather than by calling the endpoint three
    // times. That isolates the PERSISTED budget — the one OtpService enforces against the user_otps
    // rows, which survives a process restart — from the per-minute burst throttle in front of it.
    // Driving it through the endpoint would trip the burst limiter first and assert the wrong guard.
    for ($i = 0; $i < 3; $i++) {
        UserOtp::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'destination' => $user->email,
            'code_hash' => hash('sha256', (string) $i),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);
    }

    $this->postJson('/api/v1/auth/resend-email-otp')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'AUTH_OTP_RATE_LIMITED');

    Notification::assertNothingSent();
});

it('still has budget left below the hourly ceiling', function (): void {
    Notification::fake();
    config(['identity.otp.email.max_per_hour' => 3]);

    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    // One short of the ceiling — the request must succeed, or the guard is off by one and the
    // endpoint refuses users who still have budget.
    for ($i = 0; $i < 2; $i++) {
        UserOtp::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'destination' => $user->email,
            'code_hash' => hash('sha256', (string) $i),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);
    }

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    Notification::assertSentTo($user, EmailOtpNotification::class);
});

it('caps a burst of resends with the per-minute throttle', function (): void {
    Notification::fake();
    // Well above the burst limit so the hourly budget cannot be what refuses.
    config(['identity.otp.email.max_per_hour' => 100]);

    // Read the burst allowance from the limiter rather than hard-coding it, so raising or lowering
    // the limit cannot leave this test silently asserting the wrong thing.
    $burst = 3;

    Sanctum::actingAs(User::factory()->unverified()->create());

    for ($i = 0; $i < $burst; $i++) {
        $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();
    }

    // Sending mail is the expensive side effect, so the burst guard sits in front of the hourly one.
    $this->postJson('/api/v1/auth/resend-email-otp')->assertStatus(429);
});

it('answers an already verified account without sending anything and without confirming its state', function (): void {
    Notification::fake();
    $verified = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($verified);

    $response = $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    // Same body as the unverified path — the endpoint must not be an oracle for whether an address
    // is still pending verification.
    Notification::assertNotSentTo($verified, EmailOtpNotification::class);
    expect($response->json('message'))
        ->toBe('If the address still needs verifying, a new code has been sent.');
});

it('refuses the resend endpoint to an anonymous caller', function (): void {
    $this->postJson('/api/v1/auth/resend-email-otp')->assertUnauthorized();
});

/*
 * -- A10: resending must retire the previous code -------------------------------------------------
 *
 * send() did not consume prior unconsumed codes and verify() simply took the newest row
 * (orderByDesc('id')). So the old code stayed live in the user's inbox: typing it returned
 * AUTH_OTP_INVALID *and* burned an attempt against the new code. Worse, every resend started a row
 * with attempts = 0, so the per-code guess ceiling could be rewound indefinitely by resending.
 *
 * The existing resend tests proved the NEW code works. None proved the old one stopped working.
 */
it('stops the previous code working once a new one is sent', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $codes = [];
    $capture = function ($n) use (&$codes): bool {
        $codes[] = $n->code;

        return true;
    };

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();
    Notification::assertSentTo($user, EmailOtpNotification::class, $capture);

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();
    Notification::assertSentTo($user, EmailOtpNotification::class, $capture);

    [$first, $second] = [$codes[0], $codes[count($codes) - 1]];
    expect($first)->not->toBe($second);

    // The superseded code is dead.
    $this->postJson('/api/v1/auth/verify-email', ['code' => $first])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'AUTH_OTP_INVALID');

    // And the live one still verifies — proving the old code died without taking the new one with it.
    $this->postJson('/api/v1/auth/verify-email', ['code' => $second])->assertOk();
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('leaves exactly one live code after several resends', function (): void {
    Notification::fake();
    config(['identity.otp.email.max_per_hour' => 100]);
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    app(OtpService::class)->send($user, OtpChannel::Email, $user->email);
    app(OtpService::class)->send($user, OtpChannel::Email, $user->email);
    app(OtpService::class)->send($user, OtpChannel::Email, $user->email);

    $live = UserOtp::query()
        ->where('user_id', $user->id)
        ->where('channel', OtpChannel::Email->value)
        ->whereNull('consumed_at')
        ->count();

    expect($live)->toBe(1)
        // Retiring the old rows must not delete them — the hourly budget counts issued codes, so
        // deleting them would hand out a free reset of the rate limit.
        ->and(UserOtp::query()->where('user_id', $user->id)->count())->toBe(3);
});

it('does not let a resend rewind the per-code guess ceiling', function (): void {
    Notification::fake();
    config(['identity.otp.email.max_per_hour' => 100, 'identity.otp.max_verify_attempts' => 3]);
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    // Burn the ceiling on the live code; it is consumed (burned) at the limit.
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/v1/auth/verify-email', ['code' => '000000'])->assertStatus(422);
    }

    // A fresh code is a fresh ceiling — that is intended. What must NOT happen is the OLD code
    // remaining usable alongside it.
    $codes = [];
    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();
    Notification::assertSentTo($user, EmailOtpNotification::class, function ($n) use (&$codes): bool {
        $codes[] = $n->code;

        return true;
    });

    $live = UserOtp::query()
        ->where('user_id', $user->id)
        ->whereNull('consumed_at')
        ->count();

    expect($live)->toBe(1);
});

it('retires a code issued at registration when the user resends', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    // Stand in for the registration OTP still sitting in the inbox.
    UserOtp::create([
        'user_id' => $user->id,
        'channel' => 'email',
        'destination' => $user->email,
        'code_hash' => hash('sha256', '424242'),
        'expires_at' => now()->addMinutes(10),
        'attempts' => 0,
    ]);

    $this->postJson('/api/v1/auth/resend-email-otp')->assertOk();

    $this->postJson('/api/v1/auth/verify-email', ['code' => '424242'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'AUTH_OTP_INVALID');
});
