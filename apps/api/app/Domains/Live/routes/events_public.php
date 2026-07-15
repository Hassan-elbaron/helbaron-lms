<?php

use App\Domains\Live\Http\Controllers\Api\V1\EventController;
use App\Domains\Live\Http\Controllers\Api\V1\EventRegistrationController;
use Illuminate\Support\Facades\Route;

/*
 | Public "Events" presentation surface over the existing Live domain. Base 'api' prefix +
 | these => /api/v1/events/*. Public reads are unauthenticated + throttled; registration
 | requires auth:sanctum and reuses the existing Live registration actions.
 */
Route::prefix('v1')->group(function (): void {
    // Public, marketing-safe reads (no auth), rate-limited. Gated by the 'events' feature flag
    // (default-on; admins always pass) so the surface can be switched off without a deploy.
    Route::middleware(['throttle:60,1', 'feature:events'])->group(function (): void {
        Route::get('events', [EventController::class, 'index']);
        Route::get('events/{session}', [EventController::class, 'show']);
    });

    // Authenticated registration — delegates to the existing Live actions. Same 'events' gate.
    Route::middleware(['auth:sanctum', 'feature:events'])->group(function (): void {
        Route::post('events/{session}/register', [EventRegistrationController::class, 'store']);
        Route::delete('events/{session}/register', [EventRegistrationController::class, 'destroy']);
    });
});
