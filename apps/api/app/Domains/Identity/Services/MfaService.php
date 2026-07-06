<?php

namespace App\Domains\Identity\Services;

use App\Domains\Identity\Exceptions\InvalidMfaCodeException;
use App\Domains\Identity\Exceptions\MfaAlreadyEnabledException;
use App\Domains\Identity\Exceptions\MfaNotEnabledException;
use App\Domains\Identity\Models\User;
use App\Shared\Services\BaseService;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP multi-factor authentication (RFC 6238) via google2fa, plus one-time recovery codes.
 * Secrets and recovery codes are stored encrypted on the User model.
 */
class MfaService extends BaseService
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    /**
     * Begin enrollment: generate a secret + recovery codes (not yet confirmed/enabled).
     *
     * @return array{secret: string, otpauth_url: string, recovery_codes: array<int, string>}
     */
    public function begin(User $user): array
    {
        if ($user->mfa_enabled) {
            throw new MfaAlreadyEnabledException;
        }

        $secret = $this->engine->generateSecretKey();
        $recovery = $this->generateRecoveryCodes();

        $this->transaction(function () use ($user, $secret, $recovery): void {
            $user->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => $recovery,
                'two_factor_confirmed_at' => null,
                'mfa_enabled' => false,
            ])->save();
        });

        return [
            'secret' => $secret,
            'otpauth_url' => $this->engine->getQRCodeUrl(
                (string) config('identity.mfa.issuer'),
                $user->email,
                $secret,
            ),
            'recovery_codes' => $recovery,
        ];
    }

    /** Confirm enrollment by verifying a TOTP code, then enable MFA. */
    public function confirm(User $user, string $code): void
    {
        if (empty($user->two_factor_secret)) {
            throw new MfaNotEnabledException('No pending MFA enrollment.');
        }

        if (! $this->verifyTotp($user, $code)) {
            throw new InvalidMfaCodeException;
        }

        $this->transaction(function () use ($user): void {
            $user->forceFill([
                'mfa_enabled' => true,
                'two_factor_confirmed_at' => now(),
            ])->save();
        });
    }

    /** Disable MFA after verifying a valid TOTP or recovery code. */
    public function disable(User $user, string $code): void
    {
        if (! $user->mfa_enabled) {
            throw new MfaNotEnabledException;
        }

        if (! $this->verify($user, $code)) {
            throw new InvalidMfaCodeException;
        }

        $this->transaction(function () use ($user): void {
            $user->forceFill([
                'mfa_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        });
    }

    /** Verify a TOTP code or consume a one-time recovery code. */
    public function verify(User $user, string $code): bool
    {
        if ($this->verifyTotp($user, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    private function verifyTotp(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        return (bool) $this->engine->verifyKey(
            $user->two_factor_secret,
            $code,
            (int) config('identity.mfa.window', 1),
        );
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = (array) ($user->two_factor_recovery_codes ?? []);

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_filter($codes, fn ($c) => $c !== $code));
        $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();

        return true;
    }

    /** @return array<int, string> */
    private function generateRecoveryCodes(): array
    {
        $count = (int) config('identity.mfa.recovery_code_count', 8);

        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5)))
            ->all();
    }
}
