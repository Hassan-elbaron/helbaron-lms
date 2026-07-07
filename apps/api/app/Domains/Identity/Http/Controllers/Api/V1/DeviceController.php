<?php

namespace App\Domains\Identity\Http\Controllers\Api\V1;

use App\Domains\Identity\Actions\Device\RevokeDeviceAction;
use App\Domains\Identity\Http\Resources\DeviceResource;
use App\Domains\Identity\Models\UserDevice;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()->devices()->latest('id')->get();

        return ApiResponse::success(DeviceResource::collection($devices));
    }

    public function destroy(Request $request, UserDevice $device, RevokeDeviceAction $action): JsonResponse
    {
        Gate::authorize('delete', $device);

        $action->execute($request->user(), $device);

        return ApiResponse::deleted('Device revoked.');
    }
}
