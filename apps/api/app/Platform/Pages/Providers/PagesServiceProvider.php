<?php

namespace App\Platform\Pages\Providers;

use App\Platform\Shared\Providers\BaseDomainServiceProvider;

/**
 * Wires the Static Pages CMS module: loads its migrations and the public/preview route file. A
 * small, self-contained Platform module — depends only on the Shared kernel (HtmlSanitizer / audit)
 * and Identity contracts for the admin preview gate. The admin editor lives in this module's
 * Filament/Resources (auto-discovered by the panel). No cross-context coupling.
 */
class PagesServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/pages.php'];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }
}
