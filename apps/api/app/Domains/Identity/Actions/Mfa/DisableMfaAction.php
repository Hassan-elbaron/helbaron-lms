<?php

namespace App\Domains\Identity\Actions\Mfa;

use App\Domains\Identity\Events\MfaDisabled;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\MfaService;
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
