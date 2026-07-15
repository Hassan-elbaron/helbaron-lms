<?php

namespace App\Platform\Features\Http\Controllers\Api\V1;

use App\Platform\Features\Services\FeatureFlagService;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Public, auth-OPTIONAL feature-flag map. Resolves every defined flag for the requesting user
 * (or the anonymous/guest visitor) and returns a flat { key: bool } map. The frontend consumes
 * this to gate UI; default-on semantics guarantee nothing hides on a missing/unreachable flag.
 */
class FeatureFlagController extends Controller
{
    public function __construct(private readonly FeatureFlagService $features) {}

    /** GET /api/v1/feature-flags — resolved boolean map for the current (optional) user. */
    public function index(Request $request): JsonResponse
    {
        // Resolve the token user without forcing auth (the route is public); guests get the
        // anonymous map.
        $user = $request->user('sanctum');
        $user = $user instanceof User ? $user : null;

        return ApiResponse::success([
            'flags' => $this->features->all($user),
        ]);
    }
}
