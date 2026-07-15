<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

use App\Platform\Shared\Tenancy\Contracts\CurrentTenantProvider;

/**
 * Holds the current execution context's tenant. Registered as a container singleton so any code
 * (the global TenantScope, the BelongsToTenant trait) can read "the active tenant" without
 * threading it through call sites. Implements the CurrentTenantProvider port.
 *
 * Resolution is LAZY and order-independent: if a tenant was not set explicitly (e.g. by the
 * ResolveTenant middleware), the first read resolves it via the injected TenantResolver. This
 * makes enforcement correct regardless of middleware ordering — a scoped query inside a
 * controller resolves the tenant after authentication has run.
 *
 * Bypass is re-entrant (depth-counted) and scoped to a callback, so system jobs / maintenance /
 * Administration can read across tenants explicitly without leaving tenancy globally disabled.
 */
final class TenantContext implements CurrentTenantProvider
{
    private ?TenantId $tenantId = null;

    private bool $resolved = false;

    private int $bypassDepth = 0;

    public function __construct(
        private readonly ?TenantResolver $resolver = null,
    ) {}

    /** Explicitly set the active tenant (overrides lazy resolution). */
    public function set(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->resolved = true;
    }

    /** Clear the active tenant and re-arm lazy resolution. */
    public function forget(): void
    {
        $this->tenantId = null;
        $this->resolved = false;
    }

    public function has(): bool
    {
        return $this->id() !== null;
    }

    // --- CurrentTenantProvider port ---

    public function currentTenant(): ?TenantId
    {
        return $this->id();
    }

    public function hasTenant(): bool
    {
        return $this->has();
    }

    /** The active tenant, resolving lazily via the resolver on first read when not set. */
    public function id(): ?TenantId
    {
        if (! $this->resolved) {
            $this->tenantId = $this->resolver?->resolve();
            $this->resolved = true;
        }

        return $this->tenantId;
    }

    public function isBypassed(): bool
    {
        return $this->bypassDepth > 0;
    }

    /**
     * Run a callback with tenancy bypassed (re-entrant). Intended for system jobs, maintenance
     * tasks, and explicit Administration cross-tenant operations only.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public function runWithoutTenancy(callable $callback): mixed
    {
        $this->bypassDepth++;

        try {
            return $callback();
        } finally {
            $this->bypassDepth--;
        }
    }
}
