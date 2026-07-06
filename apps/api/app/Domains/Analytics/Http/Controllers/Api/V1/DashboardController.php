<?php

namespace App\Domains\Analytics\Http\Controllers\Api\V1;

use App\Domains\Analytics\Http\Resources\DashboardResource;
use App\Domains\Analytics\Models\DashboardDefinition;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $dashboards = DashboardDefinition::query()->with('widgets')->orderByDesc('is_default')->get();

        return ApiResponse::success(DashboardResource::collection($dashboards));
    }
}
