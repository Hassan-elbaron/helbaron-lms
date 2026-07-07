<?php

namespace App\Contexts\Analytics\Http\Controllers\Api\V1;

use App\Contexts\Analytics\Http\Resources\DashboardResource;
use App\Contexts\Analytics\Models\DashboardDefinition;
use App\Platform\Shared\Support\ApiResponse;
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
