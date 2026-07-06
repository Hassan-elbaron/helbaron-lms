<?php

namespace App\Domains\Identity\Http\Resources;

use App\Domains\Identity\Models\UserProfile;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property UserProfile $resource
 */
class ProfileResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'avatar_path' => $this->resource->avatar_path,
            'bio' => $this->resource->bio,
            'gender' => $this->resource->gender,
            'date_of_birth' => $this->resource->date_of_birth?->toDateString(),
        ];
    }
}
