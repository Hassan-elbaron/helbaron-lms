<?php

namespace App\Platform\Branding\Http\Controllers\Api\V1;

use App\Platform\Branding\Http\Resources\BrandingResource;
use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Public branding endpoint. Read-only, unauthenticated, cacheable — returns the single
 * BrandSetting's defaults-merged public payload so the frontend can white-label the whole site
 * (theme CSS variables, brand name, logos, copyright, social links, metadata). Presentation only.
 */
class BrandingController extends Controller
{
    /** GET /api/v1/branding — the full, defaults-merged branding payload. */
    public function show(): JsonResponse
    {
        return ApiResponse::success(
            (new BrandingResource(BrandSetting::current()))->resolve(),
        );
    }
}
