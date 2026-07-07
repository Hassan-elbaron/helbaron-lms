<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * Port: resolves the active tenant for the current execution context (request/job), or null when
 * there is no tenant (e.g. unauthenticated, or a non-tenant-scoped context).
 *
 * Implementations live in the Platform/infrastructure layer; consumers depend on this interface.
 */
interface TenantResolver
{
    public function resolve(): ?TenantId;
}
