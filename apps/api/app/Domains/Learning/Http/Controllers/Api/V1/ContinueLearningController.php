<?php

namespace App\Domains\Learning\Http\Controllers\Api\V1;

use App\Domains\Learning\Http\Resources\ContinueLearningResource;
use App\Domains\Learning\Services\ContinueLearningService;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ContinueLearningController extends Controller
{
    public function index(Request $request, ContinueLearningService $service): JsonResponse
    {
        $items = $service->forUser($request->user())
            ->map(fn ($row) => (new ContinueLearningResource($row))->resolve());

        return ApiResponse::success($items->values());
    }
}
