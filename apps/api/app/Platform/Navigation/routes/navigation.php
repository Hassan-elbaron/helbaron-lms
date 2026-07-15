<?php

use App\Platform\Navigation\Http\Controllers\Api\V1\NavigationController;
use Illuminate\Support\Facades\Route;

/*
 | Navigation Builder public endpoints. Base 'api' prefix + these => /api/v1/navigation*.
 |  - GET /navigation               public, read-only — all active menus with their item trees.
 |  - GET /navigation/{location}    public, read-only — the enabled item tree for one location.
 | Both are unauthenticated; the frontend filters by role/auth/locale/flag client-side and keeps a
 | hardcoded fallback so navigation never disappears.
 */
Route::prefix('v1')->group(function (): void {
    Route::get('navigation', [NavigationController::class, 'index']);
    Route::get('navigation/{location}', [NavigationController::class, 'show']);
});
