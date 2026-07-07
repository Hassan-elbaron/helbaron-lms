<?php

namespace App\Contexts\Analytics\Metrics\Data;

/**
 * @property array<int, array{period: string, value: float|int}> $points
 */
final readonly class MetricResult
{
    /** @param array<int, array{period: string, value: float|int}> $points */
    public function __construct(
        public string $key,
        public float|int $total,
        public array $points = [],
    ) {}
}
