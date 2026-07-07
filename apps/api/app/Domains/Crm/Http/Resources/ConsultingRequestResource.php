<?php

namespace App\Domains\Crm\Http\Resources;

use App\Domains\Crm\Models\ConsultingRequest;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property ConsultingRequest $resource
 */
class ConsultingRequestResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'subject' => $this->resource->subject,
            'description' => $this->resource->description,
            'status' => $this->resource->status->value,
            'sla_due_at' => $this->resource->sla_due_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
