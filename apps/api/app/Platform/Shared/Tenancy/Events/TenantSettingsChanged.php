<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Events;

use App\Platform\Shared\Tenancy\Lifecycle\TenantSettings;
use App\Platform\Shared\Tenancy\TenantId;
use DateTimeImmutable;

/** A tenant's settings changed. Immutable DTO. */
final class TenantSettingsChanged implements TenantEvent
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly TenantSettings $settings,
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
