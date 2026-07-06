<?php

use App\Domains\Crm\Http\Controllers\Api\V1\ConsultingController;
use App\Domains\Crm\Http\Controllers\Api\V1\LeadController;
use App\Domains\Crm\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

/*
 | CRM endpoints (authenticated). Base 'api' prefix + these => /api/v1/*.
 */
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('organizations', [OrganizationController::class, 'index']);
    Route::get('organizations/{organization}', [OrganizationController::class, 'show']);
    Route::post('organizations/{organization}/members', [OrganizationController::class, 'storeMember']);

    Route::get('leads', [LeadController::class, 'index']);
    Route::post('leads', [LeadController::class, 'store']);

    Route::get('consulting', [ConsultingController::class, 'index']);
    Route::post('consulting/request', [ConsultingController::class, 'store']);
});
