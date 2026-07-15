<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1\Instructor;

use App\Domains\Catalog\Services\InstructorAnalyticsService;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends InstructorController
{
    public function index(Request $request, InstructorAnalyticsService $analytics): JsonResponse
    {
        $instructor = $this->instructor($request);

        return ApiResponse::success($analytics->dashboard($instructor->actorId()));
    }
}
