<?php

namespace App\Domains\Analytics\Http\Resources;

use App\Domains\Analytics\Models\ExportJob;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property ExportJob $resource
 */
class ExportJobResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'format' => $this->resource->format->value,
            'status' => $this->resource->status->value,
            'row_count' => $this->resource->row_count,
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
        ];
    }
}
