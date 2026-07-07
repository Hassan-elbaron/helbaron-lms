<?php

use App\Contexts\Analytics\Models\MetricSnapshot;
use App\Contexts\Analytics\Services\KpiEngine;
use App\Contexts\Analytics\Services\MetricRollupService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increments a daily snapshot and reads it back via the KPI engine', function () {
    $rollup = new MetricRollupService;
    $rollup->increment('enrollments');
    $rollup->increment('enrollments', 4);

    expect(MetricSnapshot::where('metric_key', 'enrollments')->count())->toBe(1)
        ->and(MetricSnapshot::where('metric_key', 'enrollments')->first()->value)->toBe(5);

    $total = app(KpiEngine::class)->total('enrollments', CarbonImmutable::now()->subDay(), CarbonImmutable::now()->addDay());
    expect($total)->toBe(5);
});
