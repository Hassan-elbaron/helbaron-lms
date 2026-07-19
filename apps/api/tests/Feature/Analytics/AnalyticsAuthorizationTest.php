<?php

use App\Contexts\Analytics\Database\Seeders\AnalyticsSeeder;
use App\Contexts\Analytics\Enums\AnalyticsPermission;
use App\Contexts\Analytics\Models\ReportDefinition;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
    $this->seed(AnalyticsSeeder::class);
});

/**
 * The KPI, dashboard and report-definition endpoints shipped with no authorization beyond
 * `auth:sanctum`, so any authenticated user could read platform-wide figures including revenue.
 * These tests pin the gate shut and pin the money boundary in particular, because that is the part
 * most likely to be loosened later by someone widening a role.
 */
function analyticsUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole(SpatieRole::findByName($role, 'web'));

    return $user;
}

it('refuses a learner every metric-driven analytics read', function (string $uri) {
    $student = analyticsUser('student');

    $this->actingAs($student, 'sanctum')->getJson($uri)->assertForbidden();
})->with([
    '/api/v1/analytics/kpis?metrics[]=enrollments',
    '/api/v1/reports',
    '/api/v1/dashboards',
]);

it('refuses a learner running a report', function () {
    $student = analyticsUser('student');
    // A real definition, so the 403 comes from the authorization gate rather than from
    // FormRequest validation rejecting a bogus id first.
    $report = ReportDefinition::factory()->create(['metric_keys' => ['enrollments']]);

    $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/reports/run', ['report' => $report->public_id])
        ->assertForbidden();
});

it('refuses an unauthenticated caller', function () {
    $this->getJson('/api/v1/analytics/kpis?metrics[]=enrollments')->assertUnauthorized();
});

it('lets an admin read every metric including revenue', function () {
    $admin = analyticsUser('admin');

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/analytics/kpis?metrics[]=enrollments&metrics[]=revenue')
        ->assertOk()
        ->assertJsonPath('data.kpis.0.metric', 'enrollments')
        ->assertJsonPath('data.kpis.1.metric', 'revenue');
});

it('lets a super_admin through without an explicit permission grant', function () {
    $root = analyticsUser('super_admin');

    $this->actingAs($root, 'sanctum')
        ->getJson('/api/v1/analytics/kpis?metrics[]=revenue')
        ->assertOk()
        ->assertJsonPath('data.kpis.0.metric', 'revenue');
});

it('grants instructors analytics access but drops money metrics from the response', function () {
    $instructor = analyticsUser('instructor');

    $response = $this->actingAs($instructor, 'sanctum')
        ->getJson('/api/v1/analytics/kpis?metrics[]=enrollments&metrics[]=revenue')
        ->assertOk();

    // The entitled metric survives; the money one is dropped rather than the whole request failing,
    // so a mixed dashboard still renders what the caller may see.
    expect($response->json('data.kpis'))->toHaveCount(1)
        ->and($response->json('data.kpis.0.metric'))->toBe('enrollments');

    expect($response->getContent())->not->toContain('currency_minor');
});

it('grants instructors the analytics permission but not the revenue one', function () {
    $instructor = analyticsUser('instructor');

    // Asserted against the `web` guard explicitly. `$user->can()` would resolve the Sanctum guard,
    // which these permissions are not registered under, and would answer false for both — passing
    // the second half of this test for entirely the wrong reason. The API gate itself is
    // role-based for that same reason; these grants are what Filament reads on the web guard.
    expect($instructor->hasPermissionTo(AnalyticsPermission::ViewAnalytics->value, 'web'))->toBeTrue()
        ->and($instructor->hasPermissionTo(AnalyticsPermission::ViewRevenue->value, 'web'))->toBeFalse();
});

it('lets an instructor list dashboards and report definitions', function () {
    $instructor = analyticsUser('instructor');

    $this->actingAs($instructor, 'sanctum')->getJson('/api/v1/dashboards')->assertOk();
    $this->actingAs($instructor, 'sanctum')->getJson('/api/v1/reports')->assertOk();
});

it('refuses to run a money-bearing report for an instructor', function () {
    $instructor = analyticsUser('instructor');
    $report = ReportDefinition::factory()->create(['metric_keys' => ['enrollments', 'revenue']]);

    // A report is one computed artifact — silently omitting its money column would misrepresent
    // the result under the same name, so this refuses outright rather than degrading.
    $this->actingAs($instructor, 'sanctum')
        ->postJson('/api/v1/reports/run', ['report' => $report->public_id])
        ->assertForbidden();
});

it('runs a money-free report for an instructor', function () {
    $instructor = analyticsUser('instructor');
    $report = ReportDefinition::factory()->create(['metric_keys' => ['enrollments', 'completions']]);

    $this->actingAs($instructor, 'sanctum')
        ->postJson('/api/v1/reports/run', ['report' => $report->public_id])
        ->assertOk();
});

it('runs a money-bearing report for an admin', function () {
    $admin = analyticsUser('admin');
    $report = ReportDefinition::factory()->create(['metric_keys' => ['revenue']]);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/reports/run', ['report' => $report->public_id])
        ->assertOk();
});

it('keeps the admin-gated insight reports closed to instructors', function () {
    $instructor = analyticsUser('instructor');

    // ViewAnalytics widens the metric surface only. The operational insight reports read
    // cross-context operational tables and stay administrator-only.
    $this->actingAs($instructor, 'sanctum')
        ->getJson('/api/v1/reports/insights/catalog')
        ->assertForbidden();
});
