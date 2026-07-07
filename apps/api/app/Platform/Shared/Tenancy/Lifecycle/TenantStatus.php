<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

/**
 * Tenant lifecycle state machine (A2-S04 foundation).
 *
 * Transitions: Provisioning -> Active | Archived; Active <-> Suspended; Active|Suspended -> Archived;
 * Archived -> Active (restore). Concrete transition side effects (persistence, workflows) are
 * implemented later by Administration via the TenantProvisioner port — not here.
 */
enum TenantStatus: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Provisioning => in_array($target, [self::Active, self::Archived], true),
            self::Active => in_array($target, [self::Suspended, self::Archived], true),
            self::Suspended => in_array($target, [self::Active, self::Archived], true),
            self::Archived => $target === self::Active,
        };
    }

    /** Whether tenant resources are operational (only Active). */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
