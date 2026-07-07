<?php

use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('redirects guests from the admin panel to login', function () {
    $this->get('/admin')->assertRedirect();
});

it('forbids authenticated non-admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows active admins to reach the dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

it('blocks inactive admins via canAccessPanel', function () {
    $admin = User::factory()->inactive()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertForbidden();
});
