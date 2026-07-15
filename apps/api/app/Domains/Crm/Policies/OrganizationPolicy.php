<?php

namespace App\Domains\Crm\Policies;

use App\Domains\Crm\Models\Organization;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class OrganizationPolicy extends BasePolicy
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
        return $user->can('crm.view') || $user->can('crm.organizations.manage');
    }

    public function view(Actor $user, Organization $organization): bool
    {
        return $user->can('crm.view') || $user->can('crm.organizations.manage');
    }

    public function manage(Actor $user, Organization $organization): bool
    {
        return $user->can('crm.organizations.manage');
    }
}
