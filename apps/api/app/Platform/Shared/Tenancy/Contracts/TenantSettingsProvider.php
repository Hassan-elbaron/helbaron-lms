<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\TenantSettings;
use App\Platform\Shared\Tenancy\TenantId;

/** Port (read side): settings for a tenant. Implemented later. */
interface TenantSettingsProvider
{
    public function settingsFor(TenantId $id): TenantSettings;
}
