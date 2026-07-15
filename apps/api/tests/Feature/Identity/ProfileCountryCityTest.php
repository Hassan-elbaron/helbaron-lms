<?php

use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('persists country and city on profile update', function () {
    $user = User::factory()->create();
    $user->profile()->create([]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile', [
        'country' => 'SA',
        'city' => 'Riyadh',
        'first_name' => 'A',
    ])->assertOk()
        ->assertJsonPath('data.profile.country', 'SA')
        ->assertJsonPath('data.profile.city', 'Riyadh');

    $this->assertDatabaseHas('user_profiles', [
        'user_id' => $user->id,
        'country' => 'SA',
        'city' => 'Riyadh',
    ]);
});
