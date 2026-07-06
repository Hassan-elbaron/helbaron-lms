<?php

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('logs in with valid credentials and returns a token', function () {
    $user = User::factory()->create(['email' => 'me@example.com']);

    $res = $this->postJson('/api/v1/auth/login', [
        'email' => 'me@example.com',
        'password' => 'password',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.user.id', $user->public_id);
    expect($res->json('data.token'))->toBeString();
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'me@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'me@example.com',
        'password' => 'wrong',
    ])->assertStatus(401)->assertJsonPath('error.code', 'AUTH_INVALID_CREDENTIALS');
});

it('locks the account after too many failed attempts', function () {
    User::factory()->create(['email' => 'lock@example.com']);

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/auth/login', ['email' => 'lock@example.com', 'password' => 'wrong']);
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'lock@example.com', 'password' => 'password'])
        ->assertStatus(423)
        ->assertJsonPath('error.code', 'AUTH_ACCOUNT_LOCKED');
});

it('requires an MFA code when MFA is enabled', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    User::factory()->withMfa($secret)->create(['email' => 'mfa@example.com']);

    $this->postJson('/api/v1/auth/login', ['email' => 'mfa@example.com', 'password' => 'password'])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'AUTH_MFA_REQUIRED');

    $code = (new Google2FA)->getCurrentOtp($secret);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'mfa@example.com',
        'password' => 'password',
        'mfa_code' => $code,
    ])->assertOk();
});
