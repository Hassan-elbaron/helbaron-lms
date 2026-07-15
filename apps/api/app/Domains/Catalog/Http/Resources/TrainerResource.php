<?php

namespace App\Domains\Catalog\Http\Resources;

use App\Platform\Identity\Contracts\Data\UserRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Public trainer representation (a UserRef surfaced by the catalog). Exposes only public fields.
 *
 * @property UserRef $resource
 */
class TrainerResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->publicId,
            'name' => $this->resource->name,
            'headline' => $this->resource->headline,
            'avatar_path' => $this->resource->avatarPath,
        ];
    }
}
