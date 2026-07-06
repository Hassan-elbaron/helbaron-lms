<?php

namespace App\Domains\Crm\Actions\Lead;

use App\Domains\Crm\Enums\ActivityType;
use App\Domains\Crm\Events\LeadStageMoved;
use App\Domains\Crm\Exceptions\InvalidStageException;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\Stage;
use App\Domains\Crm\Services\ActivityLogger;
use App\Shared\Actions\BaseAction;

class MoveLeadStageAction extends BaseAction
{
    public function __construct(private readonly ActivityLogger $log) {}

    public function execute(Lead $lead, Stage $stage): Lead
    {
        if ($lead->pipeline_id !== null && $stage->pipeline_id !== $lead->pipeline_id) {
            throw new InvalidStageException;
        }

        $lead = $this->transaction(function () use ($lead, $stage): Lead {
            $lead->forceFill(['stage_id' => $stage->id])->save();
            $this->log->log($lead, ActivityType::StageChange, "Moved to stage: {$stage->name}");

            return $lead;
        });

        LeadStageMoved::dispatch($lead);

        return $lead;
    }
}
