<?php

namespace App\Platform\Identity\Actions\Auth;

use App\Platform\Identity\Events\UserLoggedIn;
use App\Platform\Identity\Exceptions\AccountInactiveException;
use App\Platform\Identity\Exceptions\AccountLockedException;
use App\Platform\Identity\Exceptions\InvalidCredentialsException;
use App\Platform\Identity\Exceptions\InvalidMfaCodeException;
use App\Platform\Identity\Exceptions\MfaRequiredException;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Services\DeviceService;
use App\Platform\Identity\Services\MfaService;
use App\Platform\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Hash;

class LoginAction extends BaseAction
{
    public function __construct(
        private readonly MfaService $mfa,
        private readonly DeviceService $devices,
    ) {}

    /**
     * @param  array{email: string, password: string, mfa_code?: ?string, device_name?: ?string, remember?: ?bool}  $data
     * @param  array{ip?: ?string, user_agent?: ?string}  $meta
     * @return array{user: User, token: string}
     */
    public function execute(array $data, array $meta = []): array
    {
        $user = User::where('email', $data['email'])->first();

        if ($user === null) {
            throw new InvalidCredentialsException;
        }

        if (! $user->is_active) {
            throw new AccountInactiveException;
        }

        if ($user->isLocked()) {
            throw new AccountLockedException;
        }

        if (! Hash::check($data['password'], $user->password)) {
            $this->registerFailedAttempt($user);
            throw new InvalidCredentialsException;
        }

        if ($user->mfa_enabled) {
            $code = $data['mfa_code'] ?? null;

            if ($code === null || $code === '') {
                throw new MfaRequiredException;
            }

            if (! $this->mfa->verify($user, $code)) {
                throw new InvalidMfaCodeException;
            }
        }

        // Reset lockout counters on success.
        $user->forceFill(['failed_login_count' => 0, 'locked_until' => null])->save();

        $token = $this->transaction(function () use ($user, $data, $meta): string {
            $newToken = $user->createToken(
                $data['device_name'] ?? 'api',
                ['*'],
                $this->tokenExpiry((bool) ($data['remember'] ?? false)),
            );
            $this->devices->register(
                $user,
                $newToken,
                $data['device_name'] ?? null,
                $meta['ip'] ?? null,
                $meta['user_agent'] ?? null,
            );

            return $newToken->plainTextToken;
        });

        UserLoggedIn::dispatch($user);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * When this token stops being accepted.
     *
     * The server-side half of "remember me". The browser cookie is the visible half — without the
     * box ticked it carries no Max-Age and dies with the browser — but a cookie the browser discards
     * is not the same as a credential the server has stopped accepting. Someone who declines to be
     * remembered on a shared machine is telling us the credential should be short-lived, and only
     * this bounds it if the token leaks by some other route.
     */
    private function tokenExpiry(bool $remember): \DateTimeInterface
    {
        return $remember
            ? now()->addDays((int) config('identity.session.remembered_days', 30))
            : now()->addHours((int) config('identity.session.session_hours', 12));
    }

    private function registerFailedAttempt(User $user): void
    {
        $max = (int) config('identity.lockout.max_attempts', 5);
        $user->increment('failed_login_count');

        if ($user->failed_login_count >= $max) {
            $user->forceFill([
                'locked_until' => now()->addMinutes((int) config('identity.lockout.minutes', 15)),
                'failed_login_count' => 0,
            ])->save();
        }
    }
}
