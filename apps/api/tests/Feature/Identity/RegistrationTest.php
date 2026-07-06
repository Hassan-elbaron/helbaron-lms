<?php

use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\EmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('registers a user and issues an email OTP', function () {
    Notification::fake();

    $res = $this->postJson('/api/v1/auth/register', [
        'name' => 'Sara Ali',
        'email' => 'sara@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $res->assertCreated()
        ->assertJsonPath('data.email', 'sara@example.com')
        ->assertJsonPath('data.email_verified', false);

    // public_id (uuid) is exposed, never the bigint id.
    expect($res->json('data.id'))->toBeString();

    $user = User::where('email', 'sara@example.com')->firstOrFail();
    expect($user->hasRole('student'))->toBeTrue();

    Notification::assertSentTo($user, EmailOtpNotification::class);
});

it('rejects duplicate emails with a validation envelope', function () {
    User::factory()->create(['email' => 'dupe@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dupe',
        'email' => 'dupe@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});
