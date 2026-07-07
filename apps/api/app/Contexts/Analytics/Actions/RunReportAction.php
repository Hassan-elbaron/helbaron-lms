<?php

namespace App\Contexts\Analytics\Actions;

use App\Contexts\Analytics\Events\ReportGenerated;
use App\Contexts\Analytics\Models\ReportDefinition;
use App\Contexts\Analytics\Models\ReportRun;
use App\Contexts\Analytics\Services\ReportingEngine;
use App\Platform\Shared\Actions\BaseAction;

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
