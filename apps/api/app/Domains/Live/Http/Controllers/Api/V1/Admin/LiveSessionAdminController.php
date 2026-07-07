<?php

namespace App\Domains\Live\Http\Controllers\Api\V1\Admin;

use App\Domains\Live\Actions\Session\CancelSessionAction;
use App\Domains\Live\Actions\Session\CompleteSessionAction;
use App\Domains\Live\Actions\Session\RescheduleSessionAction;
use App\Domains\Live\Actions\Session\ScheduleSessionAction;
use App\Domains\Live\Actions\Session\StartSessionAction;
use App\Domains\Live\Http\Requests\RescheduleSessionRequest;
use App\Domains\Live\Http\Requests\ScheduleSessionRequest;
use App\Domains\Live\Http\Resources\LiveSessionResource;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class LiveSessionAdminController extends Controller
{
    public function store(ScheduleSessionRequest $request, ScheduleSessionAction $action): JsonResponse
    {
        Gate::authorize('manage', LiveSession::class);

        return ApiResponse::created(new LiveSessionResource($action->execute($request->validated())->load('trainers')), 'Session scheduled.');
    }

    public function reschedule(RescheduleSessionRequest $request, LiveSession $session, RescheduleSessionAction $action): JsonResponse
    {
        Gate::authorize('manage', LiveSession::class);

        return ApiResponse::updated(new LiveSessionResource($action->execute($session, $request->validated())));
    }

    public function start(LiveSession $session, StartSessionAction $action): JsonResponse
    {
        Gate::authorize('manage', LiveSession::class);

        return ApiResponse::updated(new LiveSessionResource($action->execute($session)));
    }

    public function complete(LiveSession $session, CompleteSessionAction $action): JsonResponse
    {
        Gate::authorize('manage', LiveSession::class);

        return ApiResponse::updated(new LiveSessionResource($action->execute($session)));
    }

    public function cancel(LiveSession $session, CancelSessionAction $action): JsonResponse
    {
        Gate::authorize('manage', LiveSession::class);

        return ApiResponse::updated(new LiveSessionResource($action->execute($session)));
    }
}
