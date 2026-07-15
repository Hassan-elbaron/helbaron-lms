<?php

namespace App\Platform\Identity\Adapters;

use App\Platform\Identity\Contracts\UserPermissionPort;
use App\Platform\Identity\Models\User;

/**
 * Permission decisions by user id, delegating to the user's Gate ability check (`can`) — the same
 * mechanism `$user->can(...)` uses today. No rule definitions live here. Lives inside Identity.
 */
final class UserPermissionAdapter implements UserPermissionPort
{
    public function can(int $userId, string $permission): bool
    {
        return User::query()->find($userId)?->can($permission) ?? false;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function canAny(int $userId, array $permissions): bool
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
