<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * Default bypass policy: never bypass. Used until a context (Identity/Administration) binds a
 * concrete policy, and for isolated Shared tests.
 */
final class NullTenancyBypassPolicy implements TenancyBypassPolicy
{
    public function shouldBypassTenancy(): bool
    {
        return false;
    }
}
