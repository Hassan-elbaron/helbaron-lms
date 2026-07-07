<?php

namespace App\Domains\Catalog\Policies;

use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class CategoryPolicy extends BasePolicy
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
        return $user->can('catalog.categories.manage');
    }
}
