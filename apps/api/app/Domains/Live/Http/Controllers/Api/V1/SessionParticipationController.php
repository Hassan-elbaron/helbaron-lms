<?php

namespace App\Domains\Live\Http\Controllers\Api\V1;

use App\Domains\Live\Actions\Registration\JoinSessionAction;
use App\Domains\Live\Actions\Registration\RecordAttendanceAction;
use App\Domains\Live\Actions\Registration\RegisterForSessionAction;
use App\Domains\Live\Http\Requests\RecordAttendanceRequest;
use App\Domains\Live\Http\Resources\RegistrationResource;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SessionParticipationController extends Controller
{
    public function register(Request $request, LiveSession $session, RegisterForSessionAction $action): JsonResponse
    {
        $registration = $action->execute($session, $request->user());

        return ApiResponse::created(new RegistrationResource($registration), 'Registered.');
    }

    public function join(Request $request, LiveSession $session, JoinSessionAction $action): JsonResponse
    {
        return ApiResponse::success($action->execute($session, $request->user()));
    }

    public function attendance(RecordAttendanceRequest $request, LiveSession $session, RecordAttendanceAction $action): JsonResponse
    {
        $attendance = $action->execute($session, $request->user(), $request->validated());

        return ApiResponse::success([
            'joined_at' => $attendance->joined_at?->toIso8601String(),
            'left_at' => $attendance->left_at?->toIso8601String(),
            'duration_seconds' => $attendance->duration_seconds,
        ], 'Attendance recorded.');
    }
}
