<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1;

use App\Domains\Catalog\Http\Resources\TrainerResource;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Lists trainers surfaced by the catalog: active users holding the 'instructor' role.
 * Reads the Identity User model (shared kernel); never writes Identity data.
 */
class TrainerController extends Controller
{
    public function index(): JsonResponse
    {
        $trainers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->with('profile')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(TrainerResource::collection($trainers));
    }
}
