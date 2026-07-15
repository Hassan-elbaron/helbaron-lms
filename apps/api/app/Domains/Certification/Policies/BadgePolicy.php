<?php

namespace App\Domains\Certification\Policies;

use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class BadgePolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function manage(Actor $user): bool
    {
        return $user->can('certification.badges.manage');
    }
}
