<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/**
 * Marker/contract for all tenant lifecycle events. Every tenant event is an immutable DTO carrying
 * the tenant it concerns and when it occurred. No Eloquent, no framework/infrastructure coupling.
 */
interface TenantEvent
{
    public function tenantId(): TenantId;

    public function occurredAt(): DateTimeImmutable;
}
