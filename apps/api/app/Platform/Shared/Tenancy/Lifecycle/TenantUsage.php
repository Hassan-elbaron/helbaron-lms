<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

/**
 * Immutable per-tenant usage snapshot (A2-S04 foundation). Current counts per metric (members,
 * courses, storage, seats, ...). Live tracking is provided later via the TenantUsageTracker port.
 */
final class TenantUsage
{
    /** @param array<string, int> $counts */
    public function __construct(
        private readonly array $counts = [],
    ) {}

    public function count(string $key): int
    {
        return $this->counts[$key] ?? 0;
    }

    /** @return array<string, int> */
    public function all(): array
    {
        return $this->counts;
    }

    /** True when no tracked metric exceeds its limit. */
    public function within(TenantLimits $limits): bool
    {
        foreach ($this->counts as $key => $current) {
            if ($limits->exceeds($key, $current)) {
                return false;
            }
        }

        return true;
    }
}
