<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Route;

/*
 | HElbaron REST API — version 1.
 | Base prefix 'api' is applied by the framework, so these resolve under /api/v1/*.
 | Domain routes are loaded by each domain's service provider.
 */
Route::prefix('v1')->group(function (): void {
    // Liveness (backward-compatible with the original health closure shape).
    Route::get('health', [HealthController::class, 'live'])->name('api.v1.health');
    Route::get('health/live', [HealthController::class, 'live'])->name('api.v1.health.live');
    Route::get('health/ready', [HealthController::class, 'ready'])->name('api.v1.health.ready');

    // Build provenance + schema state. Answers "which build is this academy running, and did its
    // migrations actually apply?" across the fleet. See VersionController for why it is public.
    Route::get('version', VersionController::class)->name('api.v1.version');
});
