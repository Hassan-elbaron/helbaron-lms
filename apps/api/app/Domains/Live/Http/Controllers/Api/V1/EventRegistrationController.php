<?php

namespace App\Domains\Live\Http\Controllers\Api\V1;

use App\Domains\Live\Actions\Registration\CancelRegistrationAction;
use App\Domains\Live\Actions\Registration\RegisterForSessionAction;
use App\Domains\Live\Http\Resources\RegistrationResource;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Authenticated event registration for the public Events surface. Registration LOGIC is NOT
 * reimplemented here — it delegates to the existing Live actions (RegisterForSessionAction
 * handles the capacity → waitlist decision under a lock; CancelRegistrationAction cancels).
 */
class EventRegistrationController extends Controller
{
    public function store(Request $request, LiveSession $session, RegisterForSessionAction $action): JsonResponse
    {
        $registration = $action->executeByUserId($session, $request->user()->id);

        return ApiResponse::created(new RegistrationResource($registration), 'Registered.');
    }

    public function destroy(Request $request, LiveSession $session, CancelRegistrationAction $action): JsonResponse
    {
        $action->executeByUserId($session, $request->user()->id);

        return ApiResponse::deleted('Registration cancelled.');
    }
}
