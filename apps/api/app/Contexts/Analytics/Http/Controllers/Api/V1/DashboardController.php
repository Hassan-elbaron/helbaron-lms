<?php

namespace App\Contexts\Analytics\Http\Controllers\Api\V1;

use App\Contexts\Analytics\Http\Controllers\Concerns\AuthorizesAnalytics;
use App\Contexts\Analytics\Http\Resources\DashboardResource;
use App\Contexts\Analytics\Models\DashboardDefinition;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    use AuthorizesAnalytics;

    /**
     * Widget definitions carry titles and metric keys, never figures — the values are fetched
     * separately from the KPI endpoint, which applies its own revenue gate. So this listing needs
     * `analytics.view` but no money check: a caller without revenue permission may see that a
     * revenue widget exists and will simply receive no data for it.
     */
    public function index(Request $request): JsonResponse
    {
        $this->assertCanViewAnalytics($request);

        $dashboards = DashboardDefinition::query()->with('widgets')->orderByDesc('is_default')->get();

        return ApiResponse::success(DashboardResource::collection($dashboards));
    }
}
