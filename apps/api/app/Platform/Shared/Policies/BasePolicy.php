<?php

namespace App\Platform\Shared\Policies;

/**
 * Base authorization policy. Provides a `before()` extension hook that domains may override
 * to short-circuit checks (e.g. a super-admin bypass once RBAC exists). At the foundation
 * layer it grants nothing and denies nothing — it returns null so normal checks run.
 *
 * No role/permission logic lives here (that belongs to the auth/RBAC step, not the shared
 * foundation).
 *
 * @param  mixed  $user
 */
abstract class BasePolicy
{
    /**
     * Runs before any ability check. Return true to allow, false to deny, or null to fall
     * through to the specific ability method.
     */
    public function before(mixed $user, string $ability): ?bool
    {
        return null;
    }
}
