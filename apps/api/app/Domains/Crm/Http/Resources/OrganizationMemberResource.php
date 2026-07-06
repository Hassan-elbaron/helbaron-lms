<?php

namespace App\Domains\Crm\Http\Resources;

use App\Domains\Crm\Models\OrganizationMember;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property OrganizationMember $resource
 */
class OrganizationMemberResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'email' => $this->resource->email,
            'role' => $this->resource->role->value,
            'status' => $this->resource->status->value,
            'invited_at' => $this->resource->invited_at?->toIso8601String(),
        ];
    }
}
