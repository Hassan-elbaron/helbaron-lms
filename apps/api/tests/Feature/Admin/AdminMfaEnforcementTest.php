<?php

use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('blocks admins without confirmed MFA when ADMIN_REQUIRE_MFA is on', function () {
    config()->set('admin.require_mfa', true);

    $admin = User::factory()->create(); // no MFA
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertForbidden();
});

it('allows admins with confirmed MFA when ADMIN_REQUIRE_MFA is on', function () {
    config()->set('admin.require_mfa', true);

    $admin = User::factory()->withMfa()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

it('ignores MFA state when the requirement is off', function () {
    config()->set('admin.require_mfa', false);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});
