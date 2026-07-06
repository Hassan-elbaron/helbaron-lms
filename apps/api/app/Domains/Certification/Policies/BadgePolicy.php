<?php

namespace App\Domains\Certification\Policies;

use App\Domains\Identity\Models\User;
use App\Shared\Policies\BasePolicy;

class BadgePolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function manage(User $user): bool
    {
        return $user->can('certification.badges.manage');
    }
}
