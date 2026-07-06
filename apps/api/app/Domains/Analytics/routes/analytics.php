<?php

use App\Domains\Analytics\Http\Controllers\Api\V1\DashboardController;
use App\Domains\Analytics\Http\Controllers\Api\V1\ExportController;
use App\Domains\Analytics\Http\Controllers\Api\V1\KpiController;
use App\Domains\Analytics\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

/*
 | Analytics endpoints. Base 'api' prefix + these => /api/v1/*.
 */
Route::prefix('v1')->group(function (): void {
    // Signed export file stream (no auth guard; signature authorizes)
    Route::get('analytics/exports/{export}/file', [ExportController::class, 'file'])
        ->middleware('signed')
        ->name('analytics.exports.file');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('reports', [ReportController::class, 'index']);
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::post('reports/run', [ReportController::class, 'run']);

        Route::get('dashboards', [DashboardController::class, 'index']);
        Route::get('analytics/kpis', [KpiController::class, 'index']);

        // Asynchronous exports
        Route::post('analytics/exports', [ExportController::class, 'store']);
        Route::get('analytics/exports/{export}', [ExportController::class, 'show']);
    });
});
