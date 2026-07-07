<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

use App\Platform\Shared\Tenancy\TenantId;

/**
 * Port: per-tenant usage tracking. Implemented later (Administration/Analytics) to record and
 * report resource usage against TenantLimits. Foundation only — no implementation here.
 */
interface TenantUsageTracker
{
    public function usageFor(TenantId $id): TenantUsage;

    public function increment(TenantId $id, string $metric, int $by = 1): void;

    public function decrement(TenantId $id, string $metric, int $by = 1): void;
}
