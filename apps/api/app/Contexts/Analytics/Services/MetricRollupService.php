<?php

namespace App\Contexts\Analytics\Services;

use App\Contexts\Analytics\Models\MetricSnapshot;
use App\Platform\Shared\Services\BaseService;
use Carbon\CarbonInterface;

/**
 * Maintains the metric_snapshots read model. Increments converge per
 * (metric, granularity, period, dimension) so the bucket is idempotent by key.
 */
class MetricRollupService extends BaseService
{
    public function increment(string $metricKey, int $amount = 1, ?CarbonInterface $when = null, string $dimensionKey = '', string $dimensionValue = ''): void
    {
        $period = ($when ?? now())->toDateString();

        $this->transaction(function () use ($metricKey, $amount, $period, $dimensionKey, $dimensionValue): void {
            $snapshot = MetricSnapshot::firstOrCreate(
                [
                    'metric_key' => $metricKey,
                    'granularity' => 'daily',
                    'period' => $period,
                    'dimension_key' => $dimensionKey,
                    'dimension_value' => $dimensionValue,
                ],
                ['value' => 0],
            );

            $snapshot->increment('value', $amount);
        });
    }
}
