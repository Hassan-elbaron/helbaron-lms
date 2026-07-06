<?php

namespace App\Domains\Analytics\Metrics\Providers;

use App\Domains\Analytics\Contracts\Metric;
use App\Domains\Analytics\Metrics\Data\MetricQuery;
use App\Domains\Analytics\Metrics\Data\MetricResult;
use App\Domains\Analytics\Services\KpiEngine;

/**
 * A metric backed directly by the snapshot read model.
 */
class SnapshotMetric implements Metric
{
    public function __construct(private readonly KpiEngine $kpi) {}

    public function compute(MetricQuery $query): MetricResult
    {
        return new MetricResult(
            key: $query->key,
            total: $this->kpi->total($query->key, $query->from, $query->to),
            points: $this->kpi->series($query->key, $query->from, $query->to),
        );
    }
}
