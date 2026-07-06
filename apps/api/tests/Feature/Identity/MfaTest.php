<?php

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('enables MFA via enroll then verify', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $enroll = $this->postJson('/api/v1/auth/mfa/enable')->assertOk();
    $secret = $enroll->json('data.secret');

    expect($secret)->toBeString()
        ->and($enroll->json('data.otpauth_url'))->toContain('otpauth://')
        ->and($enroll->json('data.recovery_codes'))->toHaveCount(8);

    $code = (new Google2FA)->getCurrentOtp($secret);
    $this->postJson('/api/v1/auth/mfa/verify', ['code' => $code])->assertOk();

    expect($user->fresh()->mfa_enabled)->toBeTrue();
});

it('rejects an invalid MFA confirmation code', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/mfa/enable')->assertOk();

    $this->postJson('/api/v1/auth/mfa/verify', ['code' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'AUTH_MFA_INVALID');
});
