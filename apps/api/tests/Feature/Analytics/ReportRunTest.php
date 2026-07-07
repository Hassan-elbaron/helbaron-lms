<?php

use App\Contexts\Analytics\Models\MetricSnapshot;
use App\Contexts\Analytics\Models\ReportDefinition;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('runs a report from the read model and returns metric totals', function () {
    MetricSnapshot::factory()->create(['metric_key' => 'enrollments', 'period' => now()->toDateString(), 'value' => 7]);
    MetricSnapshot::factory()->create(['metric_key' => 'completions', 'period' => now()->toDateString(), 'value' => 3]);

    $report = ReportDefinition::factory()->create(['metric_keys' => ['enrollments', 'completions']]);

    Sanctum::actingAs(User::factory()->create());

    $res = $this->postJson('/api/v1/reports/run', ['report' => $report->public_id])->assertOk();

    $rows = collect($res->json('data.result.rows'))->keyBy('metric');
    expect($rows['enrollments']['total'])->toBe(7)
        ->and($rows['completions']['total'])->toBe(3);
});

it('lists reports and shows one', function () {
    $report = ReportDefinition::factory()->create(['name' => 'My Report']);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/reports')->assertOk()->assertJsonPath('data.0.name', 'My Report');
    $this->getJson("/api/v1/reports/{$report->public_id}")->assertOk()->assertJsonPath('data.id', $report->public_id);
});
