<?php

namespace App\Domains\Analytics\Services;

use App\Shared\Services\BaseService;
use Carbon\CarbonImmutable;

/**
 * Period/bucket helpers for time-series metrics.
 */
class TimeDimensionService extends BaseService
{
    public function defaultRange(): array
    {
        return [CarbonImmutable::now()->subDays(30)->startOfDay(), CarbonImmutable::now()->endOfDay()];
    }

    public function bucket(CarbonImmutable $date, string $granularity): string
    {
        return match ($granularity) {
            'monthly' => $date->format('Y-m'),
            'weekly' => $date->format('o-\WW'),
            default => $date->toDateString(),
        };
    }
}
