<?php

namespace App\Contexts\Analytics\Services;

use App\Contexts\Analytics\Models\MetricSnapshot;
use App\Platform\Shared\Services\BaseService;
use Carbon\CarbonInterface;

/**
 * Simplified cohort view: a metric bucketed by month over a range (read model only).
 */
class CohortService extends BaseService
{
    /** @return array<int, array{cohort: string, value: int}> */
    public function byMonth(string $metricKey, CarbonInterface $from, CarbonInterface $to): array
    {
        return MetricSnapshot::query()
            ->where('metric_key', $metricKey)
            ->whereBetween('period', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("to_char(period, 'YYYY-MM') as cohort, SUM(value) as value")
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->get()
            ->map(fn ($row) => ['cohort' => (string) $row->cohort, 'value' => (int) $row->value])
            ->all();
    }
}
