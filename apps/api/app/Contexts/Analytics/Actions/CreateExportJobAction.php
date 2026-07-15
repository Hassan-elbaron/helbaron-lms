<?php

namespace App\Contexts\Analytics\Actions;

use App\Contexts\Analytics\Enums\ExportStatus;
use App\Contexts\Analytics\Jobs\ProcessExportJob;
use App\Contexts\Analytics\Models\ExportJob;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Creates an export job and dispatches it asynchronously (after commit).
 */
class CreateExportJobAction extends BaseAction
{
    /** @param array<string, mixed> $params */
    public function executeByUserId(int $userId, string $format, string $source, array $params = []): ExportJob
    {
        $job = $this->transaction(function () use ($userId, $format, $source, $params): ExportJob {
            return ExportJob::create([
                'user_id' => $userId,
                'format' => $format,
                'status' => ExportStatus::Queued->value,
                'source' => $source,
                'params' => $params,
            ]);
        });

        ProcessExportJob::dispatch($job->id)->afterCommit();

        return $job;
    }
}
