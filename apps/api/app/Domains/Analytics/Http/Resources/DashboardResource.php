<?php

namespace App\Domains\Analytics\Http\Resources;

use App\Domains\Analytics\Models\DashboardDefinition;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property DashboardDefinition $resource
 */
class DashboardResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'key' => $this->resource->key,
            'name' => $this->resource->name,
            'is_default' => $this->resource->is_default,
            'widgets' => $this->whenLoaded('widgets', fn () => $this->resource->widgets->map(fn ($w) => [
                'id' => $w->public_id,
                'title' => $w->title,
                'metric_key' => $w->metric_key,
                'type' => $w->type,
                'config' => $w->config,
            ])->values()),
        ];
    }
}
