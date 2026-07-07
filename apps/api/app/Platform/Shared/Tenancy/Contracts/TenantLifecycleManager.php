<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Lifecycle\Tenant;
use App\Platform\Shared\Tenancy\Lifecycle\TenantBranding;
use App\Platform\Shared\Tenancy\Lifecycle\TenantLimits;
use App\Platform\Shared\Tenancy\Lifecycle\TenantSettings;
use App\Platform\Shared\Tenancy\Lifecycle\TenantStatus;
use App\Platform\Shared\Tenancy\TenantId;

/**
 * Port: the event-emitting management surface for a tenant. Implementations validate transitions
 * (TenantStatus::canTransitionTo), persist via the repository, and publish the matching tenant
 * events. Higher-level than the TenantProvisioner primitive. Implemented later by Administration.
 */
interface TenantLifecycleManager
{
    /** Move a tenant to a new lifecycle status (emits Activated/Suspended/Archived/Restored). */
    public function transition(TenantId $id, TenantStatus $to): Tenant;

    public function changeLimits(TenantId $id, TenantLimits $limits): Tenant;

    public function changeBranding(TenantId $id, TenantBranding $branding): Tenant;

    public function changeSettings(TenantId $id, TenantSettings $settings): Tenant;

    public function addDomain(TenantId $id, string $domain): Tenant;

    public function removeDomain(TenantId $id, string $domain): Tenant;
}
