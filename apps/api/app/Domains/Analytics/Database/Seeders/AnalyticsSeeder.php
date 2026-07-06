<?php

namespace App\Domains\Analytics\Database\Seeders;

use App\Domains\Analytics\Enums\AnalyticsPermission;
use App\Domains\Analytics\Enums\ReportType;
use App\Domains\Analytics\Models\DashboardDefinition;
use App\Domains\Analytics\Models\MetricDefinition;
use App\Domains\Analytics\Models\ReportDefinition;
use App\Domains\Identity\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds analytics permissions, the metrics catalog, a default dashboard, and a sample report.
 * Idempotent.
 */
class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (AnalyticsPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        SpatieRole::findByName(Role::Admin->value, 'web')->givePermissionTo(AnalyticsPermission::values());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ((array) config('analytics.metrics') as $key => $def) {
            MetricDefinition::firstOrCreate(['key' => $key], [
                'name' => $def['name'], 'category' => $def['category'], 'unit' => $def['unit'], 'is_active' => true,
            ]);
        }

        $dashboard = DashboardDefinition::firstOrCreate(
            ['key' => 'overview'],
            ['name' => 'Overview', 'is_default' => true],
        );
        if ($dashboard->widgets()->doesntExist()) {
            foreach (['signups', 'enrollments', 'completions', 'revenue'] as $i => $metric) {
                $dashboard->widgets()->create(['title' => ucfirst($metric), 'metric_key' => $metric, 'type' => 'kpi', 'position' => $i]);
            }
        }

        ReportDefinition::firstOrCreate(
            ['name' => 'Learning Overview'],
            ['type' => ReportType::Metric->value, 'metric_keys' => ['enrollments', 'completions'], 'visibility' => 'shared'],
        );
    }
}
