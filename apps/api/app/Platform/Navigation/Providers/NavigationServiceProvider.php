<?php

namespace App\Platform\Navigation\Providers;

use App\Platform\Shared\Providers\BaseDomainServiceProvider;

/**
 * Wires the Navigation Builder module: loads its migrations and the public navigation route file.
 * A small, self-contained Platform module — depends only on the Shared kernel (and Identity role
 * names as free strings for visibility gating). No cross-context coupling; the admin editor lives
 * in this module's Filament/Resources (auto-discovered by the panel).
 */
class NavigationServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/navigation.php'];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }
}
