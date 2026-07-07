<?php

namespace App\Platform\Shared\Providers;

use App\Platform\Shared\Http\Middleware\ResolveTenant;
use App\Platform\Shared\Tenancy\Contracts\CurrentTenantProvider;
use App\Platform\Shared\Tenancy\NullTenancyBypassPolicy;
use App\Platform\Shared\Tenancy\RequestTenantResolver;
use App\Platform\Shared\Tenancy\TenancyBypassPolicy;
use App\Platform\Shared\Tenancy\TenantContext;
use App\Platform\Shared\Tenancy\TenantMetadata;
use App\Platform\Shared\Tenancy\TenantResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the shared foundation: merges shared/features config, installs reusable schema
 * Blueprint macros (publicId, auditColumns, seoColumns), and wires the multi-tenancy foundation
 * (TenantContext singleton + TenantResolver binding + a not-yet-applied middleware alias).
 *
 * No business logic and no domain registration lives here.
 */
class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/shared.php'), 'shared');
        $this->mergeConfigFrom(base_path('config/features.php'), 'features');
        $this->mergeConfigFrom(base_path('config/tenancy.php'), 'tenancy');

        // Multi-tenancy (Sprint 1). The active tenant lives in a per-request singleton that
        // resolves lazily via the bound resolver (from the authenticated user's organization).
        // The global TenantScope + BelongsToTenant trait read this singleton; filtering only
        // occurs for models that opt into the trait and when a tenant is actually resolved.
        $this->app->bind(TenantResolver::class, RequestTenantResolver::class);
        $this->app->singleton(TenantContext::class, static function ($app): TenantContext {
            return new TenantContext($app->make(TenantResolver::class));
        });
        $this->app->bind(Curre