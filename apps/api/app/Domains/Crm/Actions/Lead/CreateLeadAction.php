<?php

namespace App\Domains\Crm\Actions\Lead;

use App\Domains\Crm\Enums\LeadStatus;
use App\Domains\Crm\Events\LeadCreated;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\Pipeline;
use App\Platform\Shared\Actions\BaseAction;

class CreateLeadAction extends BaseAction
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, int|string|null $ownerId = null): Lead
    {
        $lead = $this->transaction(function () use ($data, $ownerId): Lead {
            $pipeline = Pipeline::where('is_default', true)->first();
            $stage = $pipeline?->stages()->orderBy('position')->first();

            return Lead::create([
                'pipeline_id' => $pipeline?->id,
                'stage_id' => $stage?->id,
                'owner_id' => $ownerId,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'source' => $data['source'] ?? null,
                'status' => LeadStatus::New->value,
                'value_minor' => $data['value_minor'] ?? null,
                'currency' => $data['currency'] ?? null,
            ]);
        });

        LeadCreated::dispatch($lead);

        return $lead;
    }
}
