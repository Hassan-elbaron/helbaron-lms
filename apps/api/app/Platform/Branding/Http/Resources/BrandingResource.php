<?php

namespace App\Platform\Branding\Http\Resources;

use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * The public branding payload: the singleton's full, defaults-merged presentation settings
 * (identity, logos, theme, email + certificate branding). Everything here is presentation-only and
 * safe to expose unauthenticated — the frontend uses it to white-label the site (CSS-var theme,
 * brand name, logos, copyright, social links, metadata).
 *
 * @property BrandSetting $resource
 */
class BrandingResource extends BaseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource->toPublicArray();
    }
}
