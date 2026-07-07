<?php

namespace App\Domains\Live\Http\Controllers\Api\V1;

use App\Domains\Live\Http\Resources\LiveSessionListResource;
use App\Domains\Live\Http\Resources\LiveSessionResource;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class LiveSessionController extends Controller
{
    public function index(): JsonResponse
    {
        $sessions = LiveSession::query()
            ->upcoming()
            ->orderBy('starts_at')
            ->paginate((int) request('per_page', 15))->withQueryString();

        return ApiResponse::paginated($sessions, LiveSessionListResource::class);
    }

    public function show(LiveSession $session): JsonResponse
    {
        return ApiResponse::success(new LiveSessionResource($session->load(['trainers', 'recordings'])));
    }
}
