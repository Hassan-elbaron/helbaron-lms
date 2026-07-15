<?php

namespace App\Contexts\Analytics\Policies;

use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class DashboardDefinitionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(Actor $user): bool
    {
        return $user->can('analytics.view');
    }
}
