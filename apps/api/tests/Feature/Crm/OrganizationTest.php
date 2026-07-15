<?php

use App\Domains\Crm\Models\Organization;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
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

it('404s a malformed (non-UUID) public-id instead of a 500', function () {
    // public_id is a uuid column; a non-UUID segment must not reach the DB (Postgres 22P02 -> 500).
    // The HasPublicId route-binding guard returns null so the framework renders a clean 404.
    $this->getJson('/api/v1/organizations/34')->assertNotFound();
    $this->getJson('/api/v1/organizations/not-a-uuid')->assertNotFound();

    // A well-formed UUID that simply does not exist is also a 404 (unchanged behaviour).
    $this->getJson('/api/v1/organizations/00000000-0000-7000-8000-000000000000')->assertNotFound();
});
