<?php

namespace App\Domains\Catalog\Policies;

use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

class CategoryPolicy extends BasePolicy
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
        return $user->can('catalog.categories.manage');
    }
}
