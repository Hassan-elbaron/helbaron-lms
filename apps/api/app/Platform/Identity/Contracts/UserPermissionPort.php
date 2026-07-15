<?php

namespace App\Platform\Identity\Contracts;

/**
 * Permission *decisions* by user id — a thin facade over the framework/Spatie permission engine
 * for NON-policy business code that only needs a boolean ("may this user do X?") without importing
 * App\Platform\Identity\Models\User.
 *
 * This port declares decisions, NOT rules: no permission definitions, grants, or gate logic live
 * here. Policies themselves keep using the Gate (via the Actor interface), not this port.
 * Implemented inside the Identity context (later phase).
 */
interface UserPermissionPort
{
    /** True when the user holds the given permission. */
    public function can(int $userId, string $permission): bool;

    /**
     * True when the user holds ANY of the given permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public function canAny(int $userId, array $permissions): bool;
}
