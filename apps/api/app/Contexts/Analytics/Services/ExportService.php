<?php

namespace App\Contexts\Analytics\Services;

use App\Contexts\Analytics\Models\ReportDefinition;
use App\Platform\Shared\Services\BaseService;
use Carbon\CarbonImmutable;

/**
 * Builds a tabular dataset (headers + rows) for an export from the snapshot read model.
 */
class ExportService extends BaseService
{
    public function __construct(private readonly KpiEngine $kpi) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function datasetForReport(ReportDefinition $report, array $params = []): array
    {
        $from = isset($params['from']) ? CarbonImmutable::parse($params['from']) : CarbonImmutable::now()->subDays(30);
        $to = isset($params['to']) ? CarbonImmutable::parse($params['to']) : CarbonImmutable::now();

        $rows = [];
        foreach ((array) ($report->metric_keys ?? []) as $key) {
            $rows[] = [$key, $this->kpi->total($key, $from->startOfDay(), $to->endOfDay())];
        }

        return ['headers' => ['metric', 'total'], 'rows' => $rows];
    }
}
