<?php

use App\Domains\Notifications\Http\Controllers\Api\V1\NotificationController;
use App\Domains\Notifications\Http\Controllers\Api\V1\PreferenceController;
use Illuminate\Support\Facades\Route;

/*
 | Notification-center endpoints (authenticated). Base 'api' prefix + these => /api/v1/*.
 */
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::post('notifications/preferences', [PreferenceController::class, 'update']);
});
