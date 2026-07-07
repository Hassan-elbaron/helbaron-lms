<?php

namespace App\Domains\Identity\Http\Resources;

use App\Domains\Identity\Models\UserDevice;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property UserDevice $resource
 */
class DeviceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'ip' => $this->resource->ip,
            'user_agent' => $this->resource->user_agent,
            'last_used_at' => $this->resource->last_used_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
