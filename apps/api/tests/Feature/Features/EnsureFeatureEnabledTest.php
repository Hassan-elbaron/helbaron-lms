<?php

use App\Domains\Live\Models\LiveSession;
use App\Platform\Features\Database\Seeders\FeatureFlagsSeeder;
use App\Platform\Features\Models\FeatureFlag;
use App\Platform\Features\Services\FeatureFlagService;
use App\Platform\Features\Support\Feature;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Enums\Role;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Audit\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

/** Clear any env override + the service's per-request cache so assertions never leak. */
afterEach(function () {
    putenv('FEATURE_EVENTS');
    unset($_ENV['FEATURE_EVENTS'], $_SERVER['FEATURE_EVENTS']);
    app(FeatureFlagService::class)->flush();
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(SpatieRole::findByName(Role::Admin->value, 'web'));

    return $user;
}

it('allows the events surface when the flag is missing (default-on)', function () {
    LiveSession::factory()->create(['title' => 'Open Summit']);

    $this->getJson('/api/v1/events?filter=upcoming')->assertOk();
});

it('allows the events surface when the flag is explicitly enabled', function () {
    $this->seed(FeatureFlagsSeeder::class); // events => enabled
    LiveSession::factory()->create(['title' => 'Open Summit']);

    $this->getJson('/api/v1/events?filter=upcoming')->assertOk();
});

it('returns 404 (not 403) for a normal user when the events flag is disabled', function () {
    FeatureFlag::factory()->key('events')->disabled()->create();

    $this->getJson('/api/v1/events?filter=upcoming')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('still lets an admin through when the events flag is disabled', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('events')->disabled()->create();
    LiveSession::factory()->create(['title' => 'Admin Preview']);

    Sanctum::actingAs(adminUser());

    $this->getJson('/api/v1/events?filter=upcoming')->assertOk();
});

it('still lets a super_admin through when the events flag is disabled', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('events')->disabled()->create();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(SpatieRole::findByName(Role::SuperAdmin->value, 'web'));
    Sanctum::actingAs($superAdmin);

    $this->getJson('/api/v1/events?filter=upcoming')->assertOk();
});

it('lets the env kill-switch FEATURE_EVENTS=false force a 404 even when the DB flag is on', function () {
    FeatureFlag::factory()->key('events')->create(['is_enabled' => true]);
    putenv('FEATURE_EVENTS=false');

    $this->getJson('/api/v1/events?filter=upcoming')->assertNotFound();
});

it('lets the env override FEATURE_EVENTS=true force a pass even when the DB flag is off', function () {
    FeatureFlag::factory()->key('events')->disabled()->create();
    LiveSession::factory()->create(['title' => 'Forced On']);
    putenv('FEATURE_EVENTS=true');

    $this->getJson('/api/v1/events?filter=upcoming')->assertOk();
});

it('writes a feature.blocked audit row when a request is blocked', function () {
    FeatureFlag::factory()->key('events')->disabled()->create();

    $this->getJson('/api/v1/events?filter=upcoming')->assertNotFound();

    $row = AuditLog::query()->where('action', 'feature.blocked')->first();

    expect($row)->not->toBeNull()
        ->and($row->context['key'])->toBe('events')
        ->and($row->context['path'])->toBe('api/v1/events');
});

it('does not audit an allowed request', function () {
    LiveSession::factory()->create();

    $this->getJson('/api/v1/events?filter=upcoming')->assertOk();

    expect(AuditLog::query()->where('action', 'feature.blocked')->exists())->toBeFalse();
});

it('blocks the reports insights surface for a non-admin when the reports flag is disabled', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('reports')->disabled()->create();

    Sanctum::actingAs(User::factory()->create());

    // Middleware runs before the controller's admin check, so the response is 404 (not 403).
    $this->getJson('/api/v1/reports/insights/catalog')->assertNotFound();
});

it('keeps the reports insights surface working for an admin with the flag enabled (no regression)', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('reports')->create(['is_enabled' => true]);

    Sanctum::actingAs(adminUser());

    $this->getJson('/api/v1/reports/insights/catalog')->assertOk();
});

it('exposes a feature Gate whose result matches the service for a normal user', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('events')->disabled()->create();

    $student = User::factory()->create();
    $student->assignRole(SpatieRole::findByName(Role::Student->value, 'web'));

    $service = app(FeatureFlagService::class);
    $service->flush();

    expect(Gate::forUser($student)->allows('feature', 'events'))
        ->toBe($service->isEnabled('events', $student))
        ->toBeFalse();
});

it('grants the feature Gate to an admin even when the service says the flag is off', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('events')->disabled()->create();

    $admin = adminUser();
    $service = app(FeatureFlagService::class);
    $service->flush();

    expect(Gate::forUser($admin)->allows('feature', 'events'))->toBeTrue()
        ->and($service->isEnabled('events', $admin))->toBeFalse();
});

it('resolves the accessible() helper via the Gate (admin override honoured)', function () {
    $this->seed(RolePermissionSeeder::class);
    FeatureFlag::factory()->key('events')->disabled()->create();

    $student = User::factory()->create();
    $student->assignRole(SpatieRole::findByName(Role::Student->value, 'web'));

    expect(Feature::accessible('events', $student))->toBeFalse()
        ->and(Feature::accessible('events', adminUser()))->toBeTrue();
});
