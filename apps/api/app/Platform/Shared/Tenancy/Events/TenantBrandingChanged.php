<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\Lifecycle\TenantBranding;
use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/** A tenant's branding changed. Immutable DTO. */
final class TenantBrandingChanged implements TenantEvent
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly TenantBranding $branding,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable,
    ) {}

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
