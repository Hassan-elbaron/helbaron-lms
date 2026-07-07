<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

use App\Platform\Shared\Tenancy\TenantId;

/**
 * Port: tenant lifecycle operations (provision / activate / suspend / archive / restore).
 *
 * Foundation only — no concrete implementation and no provisioning WORKFLOW here. Administration
 * implements this later (orchestrating persistence, resource setup, events). Implementations must
 * respect TenantStatus::canTransitionTo().
 */
interface TenantProvisioner
{
    /** @param array<string, mixed> $metadata */
    public function provision(TenantId $id, array $metadata = []): Tenant;

    public function activate(TenantId $id): Tenant;

    public function suspend(TenantId $id, ?string $reason = null): Tenant;

    public function archive(TenantId $id): Tenant;

    public function restore(TenantId $id): Tenant;
}
