<?php

namespace App\Domains\Live\Http\Resources;

use App\Domains\Live\Models\LiveSession;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property LiveSession $resource
 */
class LiveSessionListResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'status' => $this->resource->status->value,
            'timezone' => $this->resource->timezone,
            'starts_at_utc' => $this->resource->starts_at?->toIso8601String(),
            'ends_at_utc' => $this->resource->ends_at?->toIso8601String(),
            'capacity' => $this->resource->capacity,
        ];
    }
}
