<?php

namespace App\Domains\Analytics\Services;

use App\Platform\Shared\Services\BaseService;
use Carbon\CarbonInterface;

/**
 * Computes a funnel from an ordered list of snapshot metrics (e.g. signups→enrollments→completions).
 */
class FunnelService extends BaseService
{
    public function __construct(private readonly KpiEngine $kpi) {}

    /**
     * @param  array<int, string>  $metricKeys
     * @return array<int, array{metric: string, value: int, conversion: float}>
     */
    public function compute(array $metricKeys, CarbonInterface $from, CarbonInterface $to): array
    {
        $steps = [];
        $previous = null;

        foreach ($metricKeys as $key) {
            $value = $this->kpi->total($key, $from, $to);
            $conversion = $previous !== null && $previous > 0 ? round(($value / $previous) * 100, 2) : 100.0;
            $steps[] = ['metric' => $key, 'value' => $value, 'conversion' => $conversion];
            $previous = $value;
        }

        return $steps;
    }
}
