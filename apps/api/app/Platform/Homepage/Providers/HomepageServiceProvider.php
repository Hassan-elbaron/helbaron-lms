<?php

namespace App\Platform\Homepage\Providers;

use App\Platform\Shared\Providers\BaseDomainServiceProvider;

/**
 * Wires the Homepage CMS module: loads its migrations and the public/preview route file. A small,
 * self-contained Platform module — depends only on the Shared kernel (and Identity contracts for
 * the admin preview gate). No cross-context coupling.
 */
class HomepageServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/homepage.php'];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }
}
