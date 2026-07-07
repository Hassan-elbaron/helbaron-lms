<?php

namespace App\Domains\Crm\Http\Resources;

use App\Domains\Crm\Models\Lead;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Lead $resource
 */
class LeadResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'source' => $this->resource->source,
            'status' => $this->resource->status->value,
            'stage' => $this->whenLoaded('stage', fn () => $this->resource->stage?->name),
            'value_minor' => $this->resource->value_minor,
            'currency' => $this->resource->currency,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
