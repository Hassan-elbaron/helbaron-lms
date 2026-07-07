<?php

namespace App\Contexts\Commerce\Policies;

use App\Platform\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

class ProductPolicy extends BasePolicy
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
        return $user->can('commerce.products.manage');
    }
}
