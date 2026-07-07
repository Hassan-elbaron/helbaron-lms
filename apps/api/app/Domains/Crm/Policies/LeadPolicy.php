<?php

namespace App\Domains\Crm\Policies;

use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class LeadPolicy extends BasePolicy
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
        return $user->can('crm.view') || $user->can('crm.leads.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.leads.manage');
    }
}
