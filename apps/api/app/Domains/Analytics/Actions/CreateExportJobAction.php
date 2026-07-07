<?php

namespace App\Domains\Analytics\Actions;

use App\Domains\Analytics\Enums\ExportStatus;
use App\Domains\Analytics\Jobs\ProcessExportJob;
use App\Domains\Analytics\Models\ExportJob;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Creates an export job and dispatches it asynchronously (after commit).
 */
class CreateExportJobAction extends BaseAction
{
    /** @param array<string, mixed> $params */
    public function execute(User $user, string $format, string $source, array $params = []): ExportJob
    {
        $job = $this->transaction(function () use ($user, $format, $source, $params): ExportJob {
            return ExportJob::create([
                'user_id' => $user->id,
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
