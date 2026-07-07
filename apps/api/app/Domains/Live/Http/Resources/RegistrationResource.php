<?php

namespace App\Domains\Live\Http\Resources;

use App\Domains\Live\Models\SessionRegistration;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property SessionRegistration $resource
 */
class RegistrationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'status' => $this->resource->status->value,
            'registered_at' => $this->resource->registered_at?->toIso8601String(),
        ];
    }
}
