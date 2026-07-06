<?php

namespace App\Domains\Identity\Actions\Mfa;

use App\Domains\Identity\Events\MfaEnabled;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\MfaService;
use App\Shared\Actions\BaseAction;

class VerifyMfaAction extends BaseAction
{
    public function __construct(private readonly MfaService $mfa) {}

    /** Confirm MFA enrollment with a TOTP code and enable it. */
    public function execute(User $user, string $code): void
    {
        $this->mfa->confirm($user, $code);

        MfaEnabled::dispatch($user);
    }
}
