<?php

use App\Domains\Assessment\Http\Controllers\Api\V1\AttemptController;
use Illuminate\Support\Facades\Route;

/**
 * Learner attempt surface. Mounted at /api/v1 by BaseDomainServiceProvider.
 *
 * Every route is authenticated and every attempt route is ownership-checked in the controller —
 * an attempt is private to the learner who sat it.
 */
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('assessments/{assessment}/attempts', [AttemptController::class, 'start']);

    Route::get('attempts/{attempt}', [AttemptController::class, 'show']);
    Route::put('attempts/{attempt}/answers', [AttemptController::class, 'answer']);
    Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit']);
});
