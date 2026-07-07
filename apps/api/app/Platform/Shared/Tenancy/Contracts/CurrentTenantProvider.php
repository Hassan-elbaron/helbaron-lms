<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\TenantId;

/** Port: exposes the currently-active tenant for the request/job. Implemented by TenantContext. */
interface CurrentTenantProvider
{
    public function currentTenant(): ?TenantId;

    public function hasTenant(): bool;
}
