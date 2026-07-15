<?php

use App\Platform\Branding\Http\Controllers\Api\V1\BrandingController;
use Illuminate\Support\Facades\Route;

/*
 | Branding / white-label endpoint. Base 'api' prefix + this => /api/v1/branding.
 |  - GET /branding   public, read-only, cacheable (the defaults-merged branding payload).
 */
Route::prefix('v1')->group(function (): void {
    Route::get('branding', [BrandingController::class, 'show']);
});
