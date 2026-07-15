<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

/**
 * Immutable per-tenant branding (A2-S04 foundation). White-label display attributes consumed later
 * by the web app / panels / emails. Null = fall back to platform defaults.
 */
final class TenantBranding
{
    public function __construct(
        public readonly ?string $displayName = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $primaryColor = null,
        public readonly ?string $theme = null,
    ) {}
}
