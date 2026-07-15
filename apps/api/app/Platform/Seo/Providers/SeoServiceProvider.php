<?php

namespace App\Platform\Seo\Providers;

use App\Platform\Shared\Providers\BaseDomainServiceProvider;

/**
 * Wires the centralized SEO Manager module: loads its migration and the public/admin route file. A
 * small, self-contained Platform module — the only cross-context reads (deriving entity defaults from
 * Catalog/Live/Identity) are centralized in the SeoResolver service. The admin editor lives in this
 * module's Filament/Resources (auto-discovered by the panel). No metadata-generation logic is
 * duplicated: the resolver is the single merge/validation point.
 */
class SeoServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/seo.php'];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }
}
