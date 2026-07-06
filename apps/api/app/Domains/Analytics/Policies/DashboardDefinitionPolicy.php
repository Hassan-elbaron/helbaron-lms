<?php

namespace App\Domains\Analytics\Policies;

use App\Domains\Identity\Models\User;
use App\Shared\Policies\BasePolicy;

class DashboardDefinitionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('analytics.view');
    }
}
