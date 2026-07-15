<?php

namespace App\Contexts\Analytics\Http\Resources;

use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Pass-through resource for a computed report payload. The ReportingService already returns a
 * fully-shaped, typed array (summary + series/rows), so the resource simply forwards it under the
 * standard success envelope, giving every report a consistent Resource-backed surface.
 *
 * @property array<string, mixed> $resource
 */
class ReportInsightResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
