<?php

use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

/**
 * E1, server-side half — the token's own lifetime must honour "remember me" too.
 *
 * The browser cookie is the visible half: unticked produces a cookie with no Max-Age, which the
 * browser discards when it closes. But a cookie the browser throws away is not the same as a
 * credential the server has stopped accepting. Someone declining to be remembered on a shared
 * machine is telling us the credential should be short-lived, and only this bounds it if the token
 * escapes by some other route — a synced profile, a backup, a shoulder-surfed devtools panel.
 *
 * `remember` was not even accepted by LoginRequest before, so it would have been stripped from the
 * payload had the client ever sent it.
 */
function loginWith(array $payload = []): TestResponse
{
    return test()->postJson('/api/v1/auth/login', array_merge([
        'email' => 'learner@academy.test',
        'password' => 'correct-horse-9',
        'device_name' => 'web',
    ], $payload));
}

function issuedToken(string $plainText): PersonalAccessToken
{
    $token = PersonalAccessToken::findToken($plainText);

    expect($token)->not->toBeNull();

    return $token;
}

beforeEach(function (): void {
    config(['identity.session.remembered_days' => 30, 'identity.session.session_hours' => 12]);

    $this->user = User::factory()->create([
        'email' => 'learner@academy.test',
        'password' => Hash::make('correct-horse-9'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
});

it('bounds a token issued without remember me', function (): void {
    $response = loginWith(['remember' => false])->assertOk();

    $token = issuedToken($response->json('data.token'));

    // Asserted against explicit instants. Carbon 3's diffIn* is SIGNED, so `diffInHours(now())` on
    // a future date is negative and `toBeLessThanOrEqual(12)` would pass for any lifetime at all —
    // a test that cannot fail. Caught by the one assertion in this file that expected a positive.
    expect($token->expires_at)->not->toBeNull()
        ->and($token->expires_at->lessThan(now()->addHours(13)))->toBeTrue()
        ->and($token->expires_at->greaterThan(now()->addHours(11)))->toBeTrue();
});

it('treats an absent flag as not remembered', function (): void {
    $response = loginWith()->assertOk();

    // The default must be the SAFE direction. Defaulting to remembered would reproduce the defect
    // for every client that has not been updated to send the field.
    expect(issuedToken($response->json('data.token'))->expires_at->lessThan(now()->addHours(13)))
        ->toBeTrue();
});

it('extends the token when remember me is chosen', function (): void {
    $response = loginWith(['remember' => true])->assertOk();

    $token = issuedToken($response->json('data.token'));

    expect($token->expires_at)->not->toBeNull()
        ->and($token->expires_at->greaterThan(now()->addDays(20)))->toBeTrue();
});

it('reads the lifetimes from configuration', function (): void {
    config(['identity.session.session_hours' => 1, 'identity.session.remembered_days' => 90]);

    $short = issuedToken(loginWith(['remember' => false])->json('data.token'));
    $long = issuedToken(loginWith(['remember' => true])->json('data.token'));

    expect($short->expires_at->lessThan(now()->addMinutes(61)))->toBeTrue()
        ->and($long->expires_at->greaterThan(now()->addDays(80)))->toBeTrue();
});

it('accepts the flag through validation', function (): void {
    // LoginRequest did not list `remember`, so it was stripped before LoginAction ever saw it.
    loginWith(['remember' => true])->assertOk();
    loginWith(['remember' => false])->assertOk();
});

/*
 * The lifecycle cases the brief names. Each asserts that a token stops working for the reason it
 * should — an expiring credential is only half the guarantee if revocation does not also hold.
 */
it('accepts a freshly issued token', function (): void {
    $token = loginWith(['remember' => false])->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/profile')
        ->assertOk();
});

it('rejects a token once it has expired', function (): void {
    $plain = loginWith(['remember' => false])->json('data.token');

    // Sanctum checks expires_at on every request; travelling past it is the whole test.
    $this->travelTo(now()->addHours(13));

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson('/api/v1/profile')
        ->assertUnauthorized();
});

it('keeps a remembered token working past the short window', function (): void {
    $plain = loginWith(['remember' => true])->json('data.token');

    $this->travelTo(now()->addDays(7));

    // Otherwise "remember me" would be a label with no effect in the other direction.
    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson('/api/v1/profile')
        ->assertOk();
});

it('rejects a revoked token immediately', function (): void {
    $plain = loginWith(['remember' => true])->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson('/api/v1/profile')
        ->assertUnauthorized();
});

it('does not resurrect a token after logout even within its lifetime', function (): void {
    $plain = loginWith(['remember' => true])->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$plain)->postJson('/api/v1/auth/logout');

    $this->travelTo(now()->addMinutes(5));

    expect(PersonalAccessToken::findToken($plain))->toBeNull();
});

/*
 * Password change. There is no authenticated "change password" route — the only path is the reset
 * flow — and ResetPasswordAction already revokes every token for the user. Asserted here rather than
 * assumed, because it is the case where a long remembered session is most dangerous: somebody
 * resetting a password usually believes their account was compromised, and a thirty-day token still
 * being honoured afterwards would defeat exactly the action they took to protect themselves.
 */
it('revokes every session when the password is reset', function (): void {
    $remembered = loginWith(['remember' => true])->json('data.token');
    $other = loginWith(['remember' => true, 'device_name' => 'phone'])->json('data.token');

    $raw = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => $this->user->email,
        'token' => Hash::make($raw),
        'created_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $this->user->email,
        'token' => $raw,
        'password' => 'brand-new-secret-1',
        'password_confirmation' => 'brand-new-secret-1',
    ])->assertOk();

    // BOTH devices, not just the one that performed the reset.
    expect(PersonalAccessToken::findToken($remembered))->toBeNull()
        ->and(PersonalAccessToken::findToken($other))->toBeNull();
});
