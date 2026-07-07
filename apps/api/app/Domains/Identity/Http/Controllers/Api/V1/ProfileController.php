<?php

namespace App\Domains\Identity\Http\Controllers\Api\V1;

use App\Domains\Identity\Actions\Profile\UpdateProfileAction;
use App\Domains\Identity\Http\Requests\UpdateProfileRequest;
use App\Domains\Identity\Http\Resources\UserResource;
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
