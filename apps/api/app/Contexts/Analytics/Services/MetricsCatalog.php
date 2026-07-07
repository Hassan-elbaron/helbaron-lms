<?php

namespace App\Contexts\Analytics\Services;

use App\Contexts\Analytics\Exceptions\UnknownMetricException;
use App\Platform\Shared\Services\BaseService;

/**
 * Registry of known metrics (from config/analytics.php).
 */
class MetricsCatalog extends BaseService
{
    /** @return array<string, array{name: string, category: string, unit: string}> */
    public function all(): array
    {
        return (array) config('analytics.metrics', []);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** @return array{name: string, category: string, unit: string} */
    public function definition(string $key): array
    {
        if (! $this->has($key)) {
            throw new UnknownMetricException("Unknown metric: {$key}");
        }

        return $this->all()[$key];
    }
}
