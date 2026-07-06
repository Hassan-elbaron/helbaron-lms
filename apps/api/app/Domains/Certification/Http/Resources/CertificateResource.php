<?php

namespace App\Domains\Certification\Http\Resources;

use App\Domains\Certification\Models\Certificate;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Owner-facing certificate view. No storage paths — download is via a signed stream URL only.
 *
 * @property Certificate $resource
 */
class CertificateResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'number' => $this->resource->number,
            'status' => $this->resource->status->value,
            'verification_code' => $this->resource->verification_code,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->resource->course->public_id,
                'title' => $this->resource->course->title,
            ]),
            'signature' => [
                'name' => $this->resource->signature_name,
                'title' => $this->resource->signature_title,
            ],
            'issued_at' => $this->resource->issued_at?->toIso8601String(),
            'revoked_at' => $this->resource->revoked_at?->toIso8601String(),
        ];
    }
}
