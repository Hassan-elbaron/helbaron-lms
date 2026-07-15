<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/** A tenant was archived (retired, retained but not operational). Immutable DTO. */
final class TenantArchived implements TenantEvent
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
