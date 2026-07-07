<?php

namespace App\Platform\Identity\Http\Resources;

use App\Platform\Identity\Models\User;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property User $resource
 */
class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'locale' => $this->resource->locale,
            'is_active' => $this->resource->is_active,
            'email_verified' => $this->resource->email_verified_at !== null,
            'phone_verified' => $this->resource->phone_verified_at !== null,
            'mfa_enabled' => $this->resource->mfa_enabled,
            'roles' => $this->resource->getRoleNames(),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
