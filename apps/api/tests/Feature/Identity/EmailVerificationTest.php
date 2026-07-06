<?php

use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\EmailOtpNotification;
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
