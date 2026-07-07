<?php

declare(strict_types=1);

namespace App\Platform\Identity\Tenancy;

use App\Platform\Shared\Tenancy\TenancyBypassPolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Identity's concrete tenancy-bypass policy: platform administrators (super_admin / admin)
 * operate across all tenants and therefore bypass tenant scoping.
 *
 * Role knowledge lives here (Identity owns RBAC), not in Shared. Reads the authenticated user via
 * the framework guard as a Model to avoid a hard dependency on the concrete User class.
 */
final class RoleBasedTenancyBypassPolicy implements TenancyBypassPolicy
{
    /** Roles whose holders bypass tenant scoping. */
    private const PLATFORM_ADMIN_ROLES = ['super_admin', 'admin'];

    public function shouldBypassTenancy(): bool
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return false;
        }

        return method_exists($user, 'hasAnyRole') && $user->hasAnyRole(self::PLATFORM_ADMIN_ROLES) === true;
    }
}
