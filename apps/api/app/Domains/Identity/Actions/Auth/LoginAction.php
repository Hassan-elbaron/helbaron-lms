<?php

namespace App\Domains\Identity\Actions\Auth;

use App\Domains\Identity\Events\UserLoggedIn;
use App\Domains\Identity\Exceptions\AccountInactiveException;
use App\Domains\Identity\Exceptions\AccountLockedException;
use App\Domains\Identity\Exceptions\InvalidCredentialsException;
use App\Domains\Identity\Exceptions\InvalidMfaCodeException;
use App\Domains\Identity\Exceptions\MfaRequiredException;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\DeviceService;
use App\Domains\Identity\Services\MfaService;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Hash;

class LoginAction extends BaseAction
{
    public function __construct(
        private readonly MfaService $mfa,
        private readonly DeviceService $devices,
    ) {}

    /**
     * @param  array{email: string, password: string, mfa_code?: ?string, device_name?: ?string}  $data
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
            $newToken = $user->createToken($data['device_name'] ?? 'api');
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
