<?php

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns the authenticated profile', function () {
    $user = User::factory()->create(['email' => 'p@example.com']);
    $user->profile()->create(['first_name' => 'Pat']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.email', 'p@example.com')
        ->assertJsonPath('data.profile.first_name', 'Pat');
});

it('updates the profile', function () {
    $user = User::factory()->create();
    $user->profile()->create([]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', [
        'name' => 'New Name',
        'first_name' => 'New',
        'last_name' => 'Name',
        'gender' => 'unspecified',
    ])->assertOk()->assertJsonPath('data.name', 'New Name');

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->profile->fresh()->first_name)->toBe('New');
});
