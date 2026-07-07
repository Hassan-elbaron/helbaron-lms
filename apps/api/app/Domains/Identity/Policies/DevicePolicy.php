<?php

namespace App\Domains\Identity\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\UserDevice;
use App\Platform\Shared\Policies\BasePolicy;

/**
 * A user may only view/revoke their own devices.
 */
class DevicePolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function delete(User $user, UserDevice $device): bool
    {
        return $device->user_id === $user->id;
    }
}
