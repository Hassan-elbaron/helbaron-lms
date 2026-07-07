<?php

namespace App\Platform\Identity\Actions\Mfa;

use App\Platform\Identity\Events\MfaEnabled;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Services\MfaService;
use App\Platform\Shared\Actions\BaseAction;

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
