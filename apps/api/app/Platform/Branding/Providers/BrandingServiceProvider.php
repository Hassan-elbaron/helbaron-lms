<?php

namespace App\Platform\Branding\Providers;

use App\Platform\Shared\Providers\BaseDomainServiceProvider;

/**
 * Wires the Branding / white-label module: loads its migration and the public branding route file.
 * A small, self-contained Platform module — depends only on the Shared kernel. No cross-context
 * coupling; the admin editor lives in this module's Filament/Resources (auto-discovered by the panel).
 */
class BrandingServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/branding.php'];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }
}
