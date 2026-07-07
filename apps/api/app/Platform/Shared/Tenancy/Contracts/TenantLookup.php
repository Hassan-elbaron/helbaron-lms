<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\Tenant;
use App\Platform\Shared\Tenancy\TenantId;

/** Port (read side): look up a tenant descriptor by id or host. Implemented later by Administration. */
interface TenantLookup
{
    public function byId(TenantId $id): ?Tenant;

    public function byDomain(string $host): ?Tenant;
}
