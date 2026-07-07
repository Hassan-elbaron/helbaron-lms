<?php

use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('grants super_admin panel access', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    expect($admin->canAccessPanel(filament()->getPanel('admin')))->toBeTrue();
});

it('denies students panel access', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});

it('denies instructors panel access by default', function () {
    $user = User::factory()->create();
    $user->assignRole('instructor');

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});
