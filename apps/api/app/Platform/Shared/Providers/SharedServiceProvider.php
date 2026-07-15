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
        $this->app->bind(CurrentTenantProvider::class, static fn ($app): TenantContext => $app->make(TenantContext::class));

        // Tenant column resolution (A2-S04): default + supported columns + per-model overrides
        // from config, so BelongsToTenant needs no change to support new tenant shapes.
        $this->app->singleton(TenantMetadata::class, static function ($app): TenantMetadata {
            /** @var array{default_column?: string, columns?: list<string>, overrides?: array<class-string, string>} $config */
            $config = (array) $app['config']->get('tenancy', []);

            return new TenantMetadata(
                $config['default_column'] ?? 'organization_id',
                $config['columns'] ?? ['organization_id'],
                $config['overrides'] ?? [],
            );
        });

        // Default tenancy-bypass policy: never bypass. Identity binds the concrete role-based
        // policy (platform admins bypass); provider order makes Identity's binding win.
        $this->app->bind(TenancyBypassPolicy::class, NullTenancyBypassPolicy::class);
    }

    public function boot(): void
    {
        $this->registerBlueprintMacros();
        $this->registerTenancyMiddlewareAlias();
    }

    /**
     * Register the tenant-resolution middleware as a named alias only. It is intentionally NOT
     * added to any middleware group/route yet, so the request pipeline is unchanged. Applying
     * this alias is the first step of A2-S02 (enforcement).
     */
    private function registerTenancyMiddlewareAlias(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant.resolve', ResolveTenant::class);
    }

    private function registerBlueprintMacros(): void
    {
        // $table->publicId(); — UUIDv7 external identifier, unique + indexed.
        if (! Blueprint::hasMacro('publicId')) {
            Blueprint::macro('publicId', function (string $column = 'public_id') {
                /** @var Blueprint $this */
                return $this->uuid($column)->unique();
            });
        }

        // $table->auditColumns(); — nullable created_by / updated_by actor references.
        if (! Blueprint::hasMacro('auditColumns')) {
            Blueprint::macro('auditColumns', function () {
                /** @var Blueprint $this */
                $this->unsignedBigInteger('created_by')->nullable();
                $this->unsignedBigInteger('updated_by')->nullable();

                return $this;
            });
        }

        // $table->seoColumns(); — JSON seo bag.
        if (! Blueprint::hasMacro('seoColumns')) {
            Blueprint::macro('seoColumns', function (string $column = 'seo') {
                /** @var Blueprint $this */
                return $this->json($column)->nullable();
            });
        }
    }
}
