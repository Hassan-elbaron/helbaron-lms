<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

/**
 * Immutable per-tenant resource limits (A2-S04 foundation). A null limit means unlimited.
 * Enforcement (blocking on limit breach) is a later story; this only models the limits.
 */
final class TenantLimits
{
    /** @param array<string, int|null> $limits null = unlimited */
    public function __construct(
        private readonly array $limits = [],
    ) {}

    public function limit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }

    public function isUnlimited(string $key): bool
    {
        return ($this->limits[$key] ?? null) === null;
    }

    /** True when $current is over the (finite) limit for $key. */
    public function exceeds(string $key, int $current): bool
    {
        $limit = $this->limits[$key] ?? null;

        return $limit !== null && $current > $limit;
    }

    /** @return array<string, int|null> */
    public function all(): array
    {
        return $this->limits;
    }
}
