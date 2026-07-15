<?php

use App\Platform\Homepage\Http\Controllers\Api\V1\HomepageController;
use Illuminate\Support\Facades\Route;

/*
 | Homepage CMS endpoints. Base 'api' prefix + these => /api/v1/homepage*.
 |  - GET /homepage          public, read-only (published snapshot).
 |  - GET /homepage/preview  authenticated + admin/super_admin (working draft, for the builder).
 */
Route::prefix('v1')->group(function (): void {
    Route::get('homepage', [HomepageController::class, 'index']);
    Route::get('homepage/preview', [HomepageController::class, 'preview'])->middleware('auth:sanctum');
});
