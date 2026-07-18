<?php

use App\Domains\Assessment\Http\Controllers\Api\V1\Admin\AssessmentAdminController;
use App\Domains\Assessment\Http\Controllers\Api\V1\Admin\QuestionAdminController;
use Illuminate\Support\Facades\Route;

/**
 * Assessment authoring. Mounted at /api/v1/admin by BaseDomainServiceProvider.
 *
 * `{course}` is a plain string (the course public_id), NOT a bound Course model — this context may
 * not import Catalog's model, so the controller resolves it through CourseAccessPort. Assessment
 * and question parameters ARE route-bound, by public_id.
 */
Route::prefix('v1/admin')->middleware('auth:sanctum')->group(function (): void {
    // Course-scoped listing and creation.
    Route::get('courses/{course}/assessments', [AssessmentAdminController::class, 'index']);
    Route::post('courses/{course}/assessments', [AssessmentAdminController::class, 'store']);

    // Assessment-scoped.
    Route::get('assessments/{assessment}', [AssessmentAdminController::class, 'show']);
    Route::put('assessments/{assessment}', [AssessmentAdminController::class, 'update']);
    Route::delete('assessments/{assessment}', [AssessmentAdminController::class, 'destroy']);
    Route::post('assessments/{assessment}/status', [AssessmentAdminController::class, 'status']);

    // Questions (with their option sets saved atomically).
    Route::post('assessments/{assessment}/questions', [QuestionAdminController::class, 'store']);
    Route::put('assessments/{assessment}/questions/order', [QuestionAdminController::class, 'reorder']);
    Route::put('questions/{question}', [QuestionAdminController::class, 'update']);
    Route::delete('questions/{question}', [QuestionAdminController::class, 'destroy']);
});
