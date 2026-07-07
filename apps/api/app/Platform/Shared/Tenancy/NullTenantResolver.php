<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * No-op resolver: always resolves to no tenant. Useful for console/system contexts and tests,
 * and as an explicit "tenancy disabled" binding.
 */
final class NullTenantResolver implements TenantResolver
{
    public function resolve(): ?TenantId
    {
        return null;
    }
}
