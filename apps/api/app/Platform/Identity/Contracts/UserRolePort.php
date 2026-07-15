<?php

namespace App\Platform\Identity\Contracts;

/**
 * Role *decisions* by user id — a thin facade over the framework/Spatie role engine for business
 * code that needs a role check or the role slugs without importing
 * App\Platform\Identity\Models\User. Role slugs are strings (super_admin, admin, instructor,
 * student), the same values the seeders assign.
 *
 * Declares decisions, NOT rules: no role/permission definitions live here. Implemented inside the
 * Identity context (later phase).
 */
interface UserRolePort
{
    /** True when the user has the given role slug. */
    public function hasRole(int $userId, string $role): bool;

    /**
     * True when the user has ANY of the given role slugs.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(int $userId, array $roles): bool;

    /**
     * All role slugs held by the user.
     *
     * @return list<string>
     */
    public function rolesFor(int $userId): array;
}
