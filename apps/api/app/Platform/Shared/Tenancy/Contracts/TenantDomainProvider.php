<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\TenantDomains;
use App\Platform\Shared\Tenancy\TenantId;

/** Port (read side): a tenant's domains, and host -> tenant resolution. Implemented later. */
interface TenantDomainProvider
{
    public function domainsFor(TenantId $id): TenantDomains;

    public function resolve(string $host): ?TenantId;
}
