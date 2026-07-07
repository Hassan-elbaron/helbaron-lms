<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

/**
 * Immutable per-tenant custom domains (A2-S04 foundation). A primary host plus optional aliases,
 * used later for host-based tenant resolution and white-label routing.
 */
final class TenantDomains
{
    /** @param list<string> $aliases */
    public function __construct(
        public readonly ?string $primary = null,
        public readonly array $aliases = [],
    ) {
    }

    /** @return list<string> */
    public function all(): array
    {
        return $this->primary === null ? $this->aliases : [$this->primary, ...$this->aliases];
    }

    public function matches(string $host): bool
    {
        return in_array($host, $this->all(), true);
    }
}
