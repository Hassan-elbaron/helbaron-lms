<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * Port: decides whether the current actor bypasses tenant scoping (e.g. platform administrators
 * who operate across all tenants).
 *
 * Shared depends ONLY on this interface — it holds no role/RBAC knowledge. Identity (and later
 * Administration) provide the concrete policy. The default binding is the no-bypass
 * NullTenancyBypassPolicy.
 */
interface TenancyBypassPolicy
{
    public function shouldBypassTenancy(): bool;
}
