<?php

namespace App\Platform\Identity\Adapters;

use App\Platform\Identity\Contracts\UserRolePort;
use App\Platform\Identity\Models\User;

/**
 * Role decisions / slug enumeration by user id, delegating to the Spatie HasRoles trait — the same
 * mechanism `$user->hasRole(...)` uses today. No role definitions live here. Lives inside Identity.
 */
final class UserRoleAdapter implements UserRolePort
{
    public function hasRole(int $userId, string $role): bool
    {
        return User::query()->find($userId)?->hasRole($role) ?? false;
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(int $userId, array $roles): bool
    {
        return User::query()->find($userId)?->hasAnyRole($roles) ?? false;
    }

    /**
     * @return list<string>
     */
    public function rolesFor(int $userId): array
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            return [];
        }

        return array_values($user->getRoleNames()->all());
    }
}
