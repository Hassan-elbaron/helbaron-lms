<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/** A new tenant was provisioned (created in the Provisioning state). Immutable DTO. */
final class TenantProvisioned implements TenantEvent
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly array $metadata = [],
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
