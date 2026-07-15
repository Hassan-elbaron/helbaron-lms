<?php

namespace App\Contexts\Analytics\Database\Seeders;

use App\Contexts\Analytics\Enums\AnalyticsPermission;
use App\Contexts\Analytics\Enums\InsightReport;
use App\Contexts\Analytics\Enums\ReportType;
use App\Contexts\Analytics\Models\DashboardDefinition;
use App\Contexts\Analytics\Models\MetricDefinition;
use App\Contexts\Analytics\Models\ReportDefinition;
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
        SpatieRole::findByName('admin', 'web')->givePermissionTo(AnalyticsPermission::values());
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

        // Register a ReportDefinition per operational report so the full report catalog is visible
        // and manageable from the existing Filament ReportDefinition resource. The `insight` filter
        // links each definition to its /api/v1/reports/insights/* endpoint.
        foreach (InsightReport::cases() as $report) {
            ReportDefinition::firstOrCreate(
                ['name' => $report->label().' Report'],
                [
                    'type' => ReportType::Table->value,
                    'metric_keys' => [],
                    'filters' => ['insight' => $report->value],
                    'visibility' => 'shared',
                ],
            );
        }
    }
}
