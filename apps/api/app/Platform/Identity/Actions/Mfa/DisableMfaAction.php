<?php

namespace App\Platform\Identity\Actions\Mfa;

use App\Platform\Identity\Events\MfaDisabled;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Services\MfaService;
use App\Platform\Shared\Actions\BaseAction;

class DisableMfaAction extends BaseAction
{
    public function __construct(private readonly MfaService $mfa) {}

    public function execute(User $user, string $code): void
    {
        $this->mfa->disable($user, $code);

        MfaDisabled::dispatch($user);
    }
}
