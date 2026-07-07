<?php

namespace App\Contexts\Analytics\Metrics\Data;

use Carbon\CarbonInterface;

final readonly class MetricQuery
{
    public function __construct(
        public string $key,
        public string $granularity,
        public CarbonInterface $from,
        public CarbonInterface $to,
        public ?string $dimensionKey = null,
        public ?string $dimensionValue = null,
    ) {}
}
