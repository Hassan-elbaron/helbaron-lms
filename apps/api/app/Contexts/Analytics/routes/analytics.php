<?php

use App\Contexts\Analytics\Http\Controllers\Api\V1\DashboardController;
use App\Contexts\Analytics\Http\Controllers\Api\V1\ExportController;
use App\Contexts\Analytics\Http\Controllers\Api\V1\KpiController;
use App\Contexts\Analytics\Http\Controllers\Api\V1\ReportController;
use App\Contexts\Analytics\Http\Controllers\Api\V1\ReportInsightController;
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
        // Operational reports (admin-gated inside the controller). Two-segment paths under
        // reports/insights/* never collide with the reports/{report} model-bound route below.
        // Gated by the 'reports' feature flag (default-on; admins always pass).
        Route::prefix('reports/insights')->middleware('feature:reports')->group(function (): void {
            Route::get('catalog', [ReportInsightController::class, 'catalog']);
            Route::get('revenue', [ReportInsightController::class, 'revenue']);
            Route::get('commerce', [ReportInsightController::class, 'commerce']);
            Route::get('course-performance', [ReportInsightController::class, 'coursePerformance']);
            Route::get('instructor-performance', [ReportInsightController::class, 'instructorPerformance']);
            Route::get('organization-performance', [ReportInsightController::class, 'organizationPerformance']);
            Route::get('certificates', [ReportInsightController::class, 'certificates']);
            Route::get('live-sessions', [ReportInsightController::class, 'liveSessions']);
            Route::get('learner-activity', [ReportInsightController::class, 'learnerActivity']);
            Route::get('completion-funnel', [ReportInsightController::class, 'completionFunnel']);
            Route::get('retention', [ReportInsightController::class, 'retention']);
            Route::get('crm', [ReportInsightController::class, 'crm']);
        });

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
