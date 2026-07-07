<?php

namespace App\Domains\Certification\Http\Resources;

use App\Domains\Certification\Models\Certificate;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Certificate $resource
 */
class CertificateListItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'number' => $this->resource->number,
            'status' => $this->resource->status->value,
            'course_title' => $this->resource->course?->title,
            'issued_at' => $this->resource->issued_at?->toIso8601String(),
        ];
    }
}
