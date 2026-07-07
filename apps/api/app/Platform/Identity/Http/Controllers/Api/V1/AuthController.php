<?php

namespace App\Platform\Identity\Http\Controllers\Api\V1;

use App\Platform\Identity\Actions\Auth\ForgotPasswordAction;
use App\Platform\Identity\Actions\Auth\LoginAction;
use App\Platform\Identity\Actions\Auth\LogoutAction;
use App\Platform\Identity\Actions\Auth\RegisterUserAction;
use App\Platform\Identity\Actions\Auth\ResetPasswordAction;
use App\Platform\Identity\Actions\Auth\VerifyEmailAction;
use App\Platform\Identity\Actions\Auth\VerifyPhoneAction;
use App\Platform\Identity\Http\Requests\ForgotPasswordRequest;
use App\Platform\Identity\Http\Requests\LoginRequest;
use App\Platform\Identity\Http\Requests\RegisterRequest;
use App\Platform\Identity\Http\Requests\ResetPasswordRequest;
use App\Platform\Identity\Http\Requests\VerifyEmailRequest;
use App\Platform\Identity\Http\Requests\VerifyPhoneRequest;
use App\Platform\Identity\Http\Resources\UserResource;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return ApiResponse::created(new UserResource($user->load('profile')), 'Registered. Please verify your email.');
    }

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute($request->validated(), [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return ApiResponse::success([
            'user' => new UserResource($result['user']->load('profile')),
            'token' => $result['token'],
        ], 'Logged in.');
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $accessToken = $request->user()->currentAccessToken();

        // Fall back to resolving the presented bearer token so the exact session is revoked
        // even when the current access token is not attached to the request user.
        if (! $accessToken instanceof PersonalAccessToken) {
            $accessToken = PersonalAccessToken::findToken((string) $request->bearerToken());
        }

        $tokenId = $accessToken?->getKey();
        $action->execute($request->user(), $tokenId !== null ? (int) $tokenId : null);

        // Drop any in-memory resolved user so no stale identity survives this request
        // (relevant under persistent runtimes and for correct stateless-logout semantics).
        Auth::forgetGuards();

        return ApiResponse::success(null, 'Logged out.');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $action->execute($request->validated()['email']);

        return ApiResponse::success(null, 'If the email exists, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $action->execute($request->validated());

        return ApiResponse::success(null, 'Password has been reset.');
    }

    public function verifyEmail(VerifyEmailRequest $request, VerifyEmailAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated()['code']);

        return ApiResponse::success(null, 'Email verified.');
    }

    public function verifyPhone(VerifyPhoneRequest $request, VerifyPhoneAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated()['code']);

        return ApiResponse::success(null, 'Phone verified.');
    }
}
