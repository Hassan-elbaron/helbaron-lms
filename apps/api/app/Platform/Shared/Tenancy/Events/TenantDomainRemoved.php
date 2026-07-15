<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/** A custom domain was removed from a tenant. Immutable DTO. */
final class TenantDomainRemoved implements TenantEvent
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly string $domain,
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
