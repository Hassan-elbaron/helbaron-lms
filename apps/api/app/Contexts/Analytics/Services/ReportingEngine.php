<?php

namespace App\Contexts\Analytics\Services;

use App\Contexts\Analytics\Models\ReportDefinition;
use App\Platform\Shared\Services\BaseService;
use Carbon\CarbonImmutable;

/**
 * Runs a report definition by reading metric values from the snapshot read model.
 */
class ReportingEngine extends BaseService
{
    public function __construct(
        private readonly KpiEngine $kpi,
        private readonly FunnelService $funnel,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(ReportDefinition $report, array $params = []): array
    {
        [$from, $to] = $this->range($params);
        $keys = (array) ($report->metric_keys ?? []);

        if ($report->type->value === 'funnel') {
            return ['type' => 'funnel', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'steps' => $this->funnel->compute($keys, $from, $to)];
        }

        $rows = [];
        foreach ($keys as $key) {
            $rows[] = ['metric' => $key, 'total' => $this->kpi->total($key, $from, $to)];
        }

        return ['type' => 'metric', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'rows' => $rows];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(array $params): array
    {
        $from = isset($params['from']) ? CarbonImmutable::parse($params['from']) : CarbonImmutable::now()->subDays(30);
        $to = isset($params['to']) ? CarbonImmutable::parse($params['to']) : CarbonImmutable::now();

        return [$from->startOfDay(), $to->endOfDay()];
    }
}
