<?php

namespace App\Contexts\Analytics\Metrics\Providers;

use App\Contexts\Analytics\Contracts\Metric;
use App\Contexts\Analytics\Metrics\Data\MetricQuery;
use App\Contexts\Analytics\Metrics\Data\MetricResult;
use App\Contexts\Analytics\Services\KpiEngine;

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
