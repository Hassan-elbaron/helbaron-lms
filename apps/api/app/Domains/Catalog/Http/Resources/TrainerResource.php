<?php

namespace App\Domains\Catalog\Http\Resources;

use App\Domains\Identity\Models\User;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Public trainer representation (a User surfaced by the catalog). Exposes only public fields.
 *
 * @property User $resource
 */
class TrainerResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'headline' => $this->resource->profile?->bio,
            'avatar_path' => $this->resource->profile?->avatar_path,
        ];
    }
}
