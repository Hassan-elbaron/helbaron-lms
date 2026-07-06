<?php

use App\Domains\Crm\Models\Organization;
use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);
});

it('lists organizations, shows one, and invites members idempotently', function () {
    $org = Organization::factory()->create();

    $this->getJson('/api/v1/organizations')->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson("/api/v1/organizations/{$org->public_id}")->assertOk()->assertJsonPath('data.id', $org->public_id);

    $this->postJson("/api/v1/organizations/{$org->public_id}/members", ['email' => 'x@corp.com', 'role' => 'manager'])
        ->assertCreated()->assertJsonPath('data.email', 'x@corp.com');

    // Idempotent per organization+email.
    $this->postJson("/api/v1/organizations/{$org->public_id}/members", ['email' => 'x@corp.com'])->assertCreated();
    expect($org->members()->count())->toBe(1);
});
