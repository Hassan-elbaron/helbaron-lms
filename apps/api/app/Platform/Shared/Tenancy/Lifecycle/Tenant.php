<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

use App\Platform\Shared\Tenancy\TenantId;

/**
 * Immutable tenant descriptor (A2-S04 foundation): ties together identity, lifecycle status,
 * settings, limits, usage, branding, custom domains, and free-form metadata.
 *
 * This is a read-model / value object — NOT an Eloquent model and NOT persisted here. Persistence
 * and lifecycle transitions are provided later by Administration (via TenantRepository /
 * TenantProvisioner). Transitions are validated by TenantStatus::canTransitionTo().
 */
final class Tenant
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly TenantId $id,
        public readonly TenantStatus $status,
        public readonly TenantSettings $settings,
        public readonly TenantLimits $limits,
        public readonly TenantUsage $usage,
        public readonly TenantBranding $branding,
        public readonly TenantDomains $domains,
        public readonly array $metadata = [],
    ) {
    }

    public function isActive(): bool
    {
        return $this->status->isOperational();
    }

    public function withStatus(TenantStatus $status): self
    {
        return new self(
            $this->id,
            $status,
            $this->settings,
            $this->limits,
            $this->usage,
            $this->branding,
            $this->domains,
            $this->metadata,
        );
    }
}
