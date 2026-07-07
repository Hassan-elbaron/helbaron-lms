<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

use Illuminate\Database\Eloquent\Model;

/**
 * Default resolver: derives the tenant from the authenticated user's `organization_id`.
 *
 * Deliberately decoupled from the Identity context: it reads the authenticated user via the
 * framework `auth()` guard as an Eloquent Model attribute, so it depends on Illuminate only
 * (no `App\Platform\Identity\Models\User` import) and does not violate context boundaries.
 *
 * This resolver knows NOTHING about roles: bypass (e.g. platform admins) is decided separately by
 * a TenancyBypassPolicy (implemented in Identity). It returns the user's organization as the
 * tenant, or null when there is no authenticated user / no organization.
 */
final class RequestTenantResolver implements TenantResolver
{
    public function resolve(): ?TenantId
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return null;
        }

        /** @var int|string|null $organizationId */
        $or