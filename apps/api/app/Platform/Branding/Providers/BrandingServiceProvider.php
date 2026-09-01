<?php

namespace App\Platform\Branding\Providers;

use App\Platform\Branding\Adapters\BrandInstallerAdapter;
use App\Platform\Branding\Adapters\BrandProfileAdapter;
use App\Platform\Branding\Adapters\TenantBrandingAdapter;
use App\Platform\Branding\Models\CustomDomain;
use App\Platform\Branding\Models\OrganizationBrandSetting;
use App\Platform\Branding\Policies\CustomDomainPolicy;
use App\Platform\Branding\Policies\OrganizationBrandPolicy;
use App\Platform\Shared\Branding\Contracts\BrandInstallerPort;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use App\Platform\Shared\Tenancy\Contracts\TenantBrandingProvider;

/**
 * Wires the Branding / white-label module: loads its migrations, the branding route file (public
 * host-resolved endpoint + org-admin brand/domain endpoints) and the per-org policies. A small,
 * self-contained Platform module — depends only on the Shared kernel and Identity CONTRACTS (the
 * Actor port used by the policies), never on CRM/other-context models. The GLOBAL brand editor lives
 * in this module's Filament/Resources (auto-discovered by the panel); per-org branding is API-managed.
 */
class BrandingServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/branding.php'];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        OrganizationBrandSetting::class => OrganizationBrandPolicy::class,
        CustomDomain::class => CustomDomainPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        // Branding owns the per-organization brand tables, so it provides the Shared read port other
        // modules use to render a company's marks — a company-branded certificate being the first
        // real consumer. The port had been declared with no implementation until now.
        $this->app->bind(TenantBrandingProvider::class, TenantBrandingAdapter::class);

        // The GLOBAL instance brand, for every outbound surface that must carry this academy's
        // identity: transactional mail, notification templates and certificates. Singleton because
        // the adapter memoises per locale and a fan-out renders many notifications per process.
        $this->app->singleton(BrandProfilePort::class, BrandProfileAdapter::class);

        // The WRITE side, kept as a separate port so the read adapter above stays literally
        // read-only on the queued-mail and certificate paths. One consumer: `install:academy`.
        // Bound, not singleton — it holds no state and runs once.
        $this->app->bind(BrandInstallerPort::class, BrandInstallerAdapter::class);
    }
}
