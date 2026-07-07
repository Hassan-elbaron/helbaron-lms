<?php

namespace App\Domains\Identity\Policies;

use App\Domains\Identity\Models\User;
use App\Platform\Shared\Policies\BasePolicy;

/**
 * Authorization for user records. A user may always act on their own account; broader
 * management is gated by the identity permissions (used by the admin panel later).
 */
class UserPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $user->can('identity.users.view');
    }

    public function update(User $user, User $target): bool
    {
        return $user->is($target) || $user->can('identity.users.manage');
    }
}
