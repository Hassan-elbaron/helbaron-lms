<?php

namespace App\Platform\Identity\Http\Controllers\Api\V1;

use App\Platform\Identity\Actions\Mfa\DisableMfaAction;
use App\Platform\Identity\Actions\Mfa\EnableMfaAction;
use App\Platform\Identity\Actions\Mfa\VerifyMfaAction;
use App\Platform\Identity\Http\Requests\MfaCodeRequest;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MfaController extends Controller
{
    public function enable(Request $request, EnableMfaAction $action): JsonResponse
    {
        $data = $action->execute($request->user());

        return ApiResponse::success($data, 'Scan the code and confirm to enable MFA.');
    }

    public function verify(MfaCodeRequest $request, VerifyMfaAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated()['code']);

        return ApiResponse::success(null, 'MFA enabled.');
    }

    public function disable(MfaCodeRequest $request, DisableMfaAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated()['code']);

        return ApiResponse::success(null, 'MFA disabled.');
    }
}
