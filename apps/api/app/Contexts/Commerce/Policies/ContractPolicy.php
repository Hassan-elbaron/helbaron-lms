<?php

namespace App\Contexts\Commerce\Policies;

use App\Contexts\Commerce\Models\Contract;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class ContractPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function accept(Actor $user, Contract $contract): bool
    {
        return $contract->user_id === $user->actorId();
    }
}
