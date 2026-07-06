<?php

namespace App\Domains\Analytics\Jobs;

use App\Domains\Analytics\Enums\ExportStatus;
use App\Domains\Analytics\Events\ExportCompleted;
use App\Domains\Analytics\Export\ExportWriterManager;
use App\Domains\Analytics\Models\ExportJob;
use App\Domains\Analytics\Models\ReportDefinition;
use App\Domains\Analytics\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the export dataset from the read model, writes CSV/XLSX, stores it privately, and marks
 * the job completed. The stored path is never exposed — downloads go through a signed route.
 */
class ProcessExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $exportJobId)
    {
        $this->onQueue('default');
    }

    public function handle(ExportService $exports, ExportWriterManager $writers): void
    {
        $job = ExportJob::find($this->exportJobId);
        if ($job === null) {
            return;
        }

        $job->forceFill(['status' => ExportStatus::Processing->value])->save();

        try {
            $params = (array) $job->params;
            $report = ReportDefinition::find($params['report_definition_id'] ?? null);
            $dataset = $report !== null
                ? $exports->datasetForReport($report, $params)
                : ['headers' => ['metric', 'total'], 'rows' => []];

            $writer = $writers->for($job->format->value);
            $bytes = $writer->write($dataset['headers'], $dataset['rows']);
            $path = 'exports/'.$job->public_id.'.'.$writer->extension();

            Storage::disk((string) config('analytics.export.disk', 'local'))->put($path, $bytes);

            $job->forceFill([
                'status' => ExportStatus::Completed->value,
                'file_path' => $path,
                'row_count' => count($dataset['rows']),
                'completed_at' => now(),
            ])->save();

            ExportCompleted::dispatch($job);
        } catch (\Throwable $e) {
            $job->forceFill(['status' => ExportStatus::Failed->value])->save();
            throw $e;
        }
    }
}
