<?php

use App\Domains\Certification\Http\Controllers\Api\V1\Admin\CertificateAdminController;
use App\Domains\Certification\Http\Controllers\Api\V1\CertificateController;
use App\Domains\Certification\Http\Controllers\Api\V1\CertificateFileController;
use App\Domains\Certification\Http\Controllers\Api\V1\MyCertificatesController;
use App\Domains\Certification\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

/*
 | Certification endpoints. Base 'api' prefix + these => /api/v1/*.
 */
Route::prefix('v1')->group(function (): void {
    // PUBLIC verification (no auth; throttled per IP against enumeration)
    Route::get('certificates/verify/{code}', [VerificationController::class, 'show'])
        ->middleware('throttle:certification-verify');

    // Signed PDF stream (no auth guard; signature authorizes)
    Route::get('certificates/{certificate}/file', CertificateFileController::class)
        ->middleware('signed')
        ->name('certificates.file');

    // Authenticated owner endpoints
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('my-certificates', [MyCertificatesController::class, 'index']);
        Route::get('certificates/{certificate}', [CertificateController::class, 'show']);
        Route::post('certificates/{certificate}/download', [CertificateController::class, 'download']);
        Route::post('certificates/{certificate}/share', [CertificateController::class, 'share']);

        // Admin (permission-gated)
        Route::post('admin/certificates/{certificate}/revoke', [CertificateAdminController::class, 'revoke']);
        Route::post('admin/certificates/{certificate}/reissue', [CertificateAdminController::class, 'reissue']);
    });
});
