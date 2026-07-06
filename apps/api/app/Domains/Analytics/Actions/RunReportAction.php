<?php

namespace App\Domains\Analytics\Actions;

use App\Domains\Analytics\Events\ReportGenerated;
use App\Domains\Analytics\Models\ReportDefinition;
use App\Domains\Analytics\Models\ReportRun;
use App\Domains\Analytics\Services\ReportingEngine;
use App\Shared\Actions\BaseAction;

/**
 * Runs a report definition against the read model and records a ReportRun.
 */
class RunReportAction extends BaseAction
{
    public function __construct(private readonly ReportingEngine $engine) {}

    /** @param array<string, mixed> $params */
    public function execute(ReportDefinition $report, array $params = []): ReportRun
    {
        $result = $this->engine->run($report, $params);

        $run = $this->transaction(function () use ($report, $params, $result): ReportRun {
            return ReportRun::create([
                'report_definition_id' => $report->id,
                'status' => 'completed',
                'params' => $params,
                'result' => $result,
                'ran_at' => now(),
            ]);
        });

        ReportGenerated::dispatch($run);

        return $run;
    }
}
