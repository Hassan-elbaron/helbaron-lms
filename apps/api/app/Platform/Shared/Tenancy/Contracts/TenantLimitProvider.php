<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\TenantLimits;
use App\Platform\Shared\Tenancy\TenantId;

/** Port (read side): resource limits for a tenant. Implemented later. */
interface TenantLimitProvider
{
    public function limitsFor(TenantId $id): TenantLimits;
}
