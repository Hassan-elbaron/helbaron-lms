<?php

namespace App\Domains\Crm\Http\Resources;

use App\Domains\Crm\Models\Organization;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class OrganizationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'status' => $this->resource->status->value,
            'size' => $this->resource->size,
            'website' => $this->resource->website,
            'members_count' => $this->whenCounted('members'),
            'members' => OrganizationMemberResource::collection($this->whenLoaded('members')),
        ];
    }
}
