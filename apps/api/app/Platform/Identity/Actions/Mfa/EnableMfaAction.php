<?php

namespace App\Platform\Identity\Actions\Mfa;

use App\Platform\Identity\Models\User;
use App\Platform\Identity\Services\MfaService;
use App\Platform\Shared\Actions\BaseAction;

class EnableMfaAction extends BaseAction
{
    public function __construct(private readonly MfaService $mfa) {}

    /** @return array{secret: string, otpauth_url: string, recovery_codes: array<int, string>} */
    public function execute(User $user): array
    {
        return $this->mfa->begin($user);
    }
}
