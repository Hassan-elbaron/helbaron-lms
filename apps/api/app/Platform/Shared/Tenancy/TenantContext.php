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
 * Administration can read across tenants explicitly without le