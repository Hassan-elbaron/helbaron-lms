<?php

use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Enums\Role;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('lists active instructors as trainers', function () {
    $instructor = User::factory()->create(['name' => 'Trainer One']);
    $instructor->assignRole(Role::Instructor->value);

    User::factory()->create(['name' => 'Regular User']); // no instructor role

    $res = $this->getJson('/api/v1/trainers')->assertOk();

    expect(collect($res->json('data'))->pluck('name'))->toContain('Trainer One')
        ->and(collect($res->json('data'))->pluck('name'))->not->toContain('Regular User');
});
