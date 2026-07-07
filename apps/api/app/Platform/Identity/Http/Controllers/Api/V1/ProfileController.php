<?php

namespace App\Platform\Identity\Http\Controllers\Api\V1;

use App\Platform\Identity\Actions\Profile\UpdateProfileAction;
use App\Platform\Identity\Http\Requests\UpdateProfileRequest;
use App\Platform\Identity\Http\Resources\UserResource;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('profile')));
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());

        return ApiResponse::updated(new UserResource($user->load('profile')));
    }
}
