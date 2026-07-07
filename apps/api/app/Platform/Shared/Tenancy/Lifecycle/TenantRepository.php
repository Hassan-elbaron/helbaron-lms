<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

use App\Platform\Shared\Tenancy\TenantId;

/**
 * Port: persistence for tenant descriptors. Implemented later by Administration against its own
 * store; no implementation exists in this story (foundation only).
 */
interface TenantRepository
{
    public function findById(TenantId $id): ?Tenant;

    public function findByDomain(string $host): ?Tenant;

    public function save(Tenant $tenant): void;
}
