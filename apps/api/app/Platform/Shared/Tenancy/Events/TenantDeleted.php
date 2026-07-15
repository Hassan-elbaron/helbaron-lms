<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/**
 * A tenant was permanently deleted. FUTURE RESERVED — the lifecycle does not yet support hard
 * deletion (Archived is terminal today). Defined now so subscribers/contracts are forward-ready.
 * Immutable DTO.
 */
final class TenantDeleted implements TenantEvent
{
    public function __construct(
        public readonly TenantId $tenantId,
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
