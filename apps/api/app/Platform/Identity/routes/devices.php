<?php

use App\Platform\Identity\Http\Controllers\Api\V1\DeviceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('devices', [DeviceController::class, 'index']);
    Route::delete('devices/{device}', [DeviceController::class, 'destroy']);
});
