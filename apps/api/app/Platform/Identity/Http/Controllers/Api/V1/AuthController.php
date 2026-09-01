<?php

namespace App\Platform\Identity\Http\Controllers\Api\V1;

use App\Platform\Identity\Actions\Auth\ForgotPasswordAction;
use App\Platform\Identity\Actions\Auth\LoginAction;
use App\Platform\Identity\Actions\Auth\LogoutAction;
use App\Platform\Identity\Actions\Auth\RegisterUserAction;
use App\Platform\Identity\Actions\Auth\ResetPasswordAction;
use App\Platform\Identity\Actions\Auth\VerifyEmailAction;
use App\Platform\Identity\Actions\Auth\VerifyPhoneAction;
use App\Platform\Identity\Enums\OtpChannel;
use App\Platform\Identity\Http\Requests\ForgotPasswordRequest;
use App\Platform\Identity\Http\Requests\LoginRequest;
use App\Platform\Identity\Http\Requests\RegisterRequest;
use App\Platform\Identity\Http\Requests\ResetPasswordRequest;
use App\Platform\Identity\Http\Requests\VerifyEmailRequest;
use App\Platform\Identity\Http\Requests\VerifyPhoneRequest;
use App\Platform\Identity\Http\Resources\UserResource;
use App\Platform\Identity\Services\OtpService;
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

    /**
     * Reissue the email verification OTP for the authenticated account.
     *
     * This closes a total lockout. RequireVerifiedEmail gates the entire api group and lets an
     * unverified account reach only profile/verify-email/logout, while the email OTP lives for ten
     * minutes — so a code that expired or landed in spam left the user with no way back in and no
     * self-service recovery anywhere in the product.
     *
     * The response is deliberately identical whether or not a code was actually sent. An already
     * verified caller gets the same body as an unverified one, so the endpoint never confirms the
     * state of an account (or the deliverability of its address) to anyone holding a token.
     *
     * Rate limiting is two-layer and neither layer is bypassable from here: throttle:
     * identity-otp-resend caps the burst, and OtpService enforces identity.otp.email.max_per_hour
     * against the persisted codes — the same budget registration issues against, reusing the same
     * issuing service rather than a second one that could drift from it.
     */
    public function resendEmailOtp(Request $request, OtpService $otp): JsonResponse
    {
        $user = $request->user();

        // Nothing to reissue for a verified account — but say so in the same words as the success
        // path, so the reply is not an oracle for whether an address is still pending.
        if ($user->getAttribute('email_verified_at') === null) {
            // OtpRateLimitedException (429) is allowed to propagate: the caller is authenticated as
            // themselves, so telling them they have exhausted their own hourly budget reveals
            // nothing, and swallowing it would leave the UI claiming a code was sent that was not.
            $otp->send($user, OtpChannel::Email, $user->email);
        }

        return ApiResponse::success(null, 'If the address still needs verifying, a new code has been sent.');
    }
}
