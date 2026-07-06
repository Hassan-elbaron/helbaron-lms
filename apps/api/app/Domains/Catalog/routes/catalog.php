<?php

use App\Domains\Catalog\Http\Controllers\Api\V1\CategoryController;
use App\Domains\Catalog\Http\Controllers\Api\V1\CourseController;
use App\Domains\Catalog\Http\Controllers\Api\V1\TrainerController;
use Illuminate\Support\Facades\Route;

/*
 | Public catalog endpoints (read-only, unauthenticated). Base 'api' prefix + these => /api/v1/*.
 */
Route::prefix('v1')->group(function (): void {
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{publicId}', [CourseController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('trainers', [TrainerController::class, 'index']);
});
