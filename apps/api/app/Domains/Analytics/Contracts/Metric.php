<?php

namespace App\Domains\Analytics\Contracts;

use App\Domains\Analytics\Metrics\Data\MetricQuery;
use App\Domains\Analytics\Metrics\Data\MetricResult;

/**
 * A metric computes a MetricResult from a MetricQuery. Implementations read ONLY from the
 * analytics read model (metric_snapshots) — never from operational tables.
 */
interface Metric
{
    public function compute(MetricQuery $query): MetricResult;
}
