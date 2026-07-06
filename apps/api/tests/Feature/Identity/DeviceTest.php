<?php

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function loginToken(): array
{
    $user = User::factory()->create(['email' => 'dev@example.com']);
    $token = test()->postJson('/api/v1/auth/login', [
        'email' => 'dev@example.com',
        'password' => 'password',
        'device_name' => 'iPhone',
    ])->json('data.token');

    return [$user, $token];
}

it('lists and revokes devices', function () {
    [$user, $token] = loginToken();

    $list = $this->withToken($token)->getJson('/api/v1/devices')->assertOk();
    expect($list->json('data'))->toHaveCount(1);

    $devicePublicId = $list->json('data.0.id');

    $this->withToken($token)->deleteJson("/api/v1/devices/{$devicePublicId}")->assertOk();
    expect($user->fresh()->devices()->count())->toBe(0);
});

it('logs out and invalidates the token', function () {
    [$user, $token] = loginToken();

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    $this->withToken($token)->getJson('/api/v1/profile')->assertStatus(401);
});
