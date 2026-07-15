<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1;

use App\Domains\Catalog\Http\Resources\TrainerResource;
use App\Platform\Identity\Contracts\UserLookupPort;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Lists trainers surfaced by the catalog: active users holding the 'instructor' role.
 * Reads through the Identity UserLookupPort (IdentityContracts) — no direct User model access.
 */
class TrainerController extends Controller
{
    public function index(UserLookupPort $users): JsonResponse
    {
        return ApiResponse::success(TrainerResource::collection($users->instructors()));
    }
}
