<?php

namespace App\Domains\Analytics\Metrics\Providers;

use App\Domains\Analytics\Contracts\Metric;
use App\Domains\Analytics\Metrics\Data\MetricQuery;
use App\Domains\Analytics\Metrics\Data\MetricResult;
use App\Domains\Analytics\Services\KpiEngine;

/**
 * A ratio of two snapshot metrics (e.g. completions / enrollments = completion rate).
 */
class RatioMetric implements Metric
{
    public function __construct(
        private readonly KpiEngine $kpi,
        private readonly string $numeratorKey,
        private readonly string $denominatorKey,
    ) {}

    public function compute(MetricQuery $query): MetricResult
    {
        $num = $this->kpi->total($this->numeratorKey, $query->from, $query->to);
        $den = $this->kpi->total($this->denominatorKey, $query->from, $query->to);

        $ratio = $den > 0 ? round(($num / $den) * 100, 2) : 0.0;

        return new MetricResult(key: $query->key, total: $ratio, points: []);
    }
}
