<?php

namespace App\Contexts\Analytics\Http\Resources;

use App\Contexts\Analytics\Models\ReportDefinition;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property ReportDefinition $resource
 */
class ReportDefinitionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'type' => $this->resource->type->value,
            'metric_keys' => $this->resource->metric_keys,
            'visibility' => $this->resource->visibility->value,
        ];
    }
}
