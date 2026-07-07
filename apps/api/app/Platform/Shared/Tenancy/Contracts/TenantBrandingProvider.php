<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\TenantBranding;
use App\Platform\Shared\Tenancy\TenantId;

/** Port (read side): branding for a tenant. Implemented later (consumed by web/panels/emails). */
interface TenantBrandingProvider
{
    public function brandingFor(TenantId $id): TenantBranding;
}
