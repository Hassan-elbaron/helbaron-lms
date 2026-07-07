<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\TenantUsage;
use App\Platform\Shared\Tenancy\TenantId;

/** Port (read side): current resource usage for a tenant. Implemented later. */
interface TenantUsageProvider
{
    public function usageFor(TenantId $id): TenantUsage;
}
