<?php

namespace App\Platform\Features\Providers;

use App\Platform\Features\Services\FeatureFlagService;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Wires the Feature Flags module: binds the evaluator as a singleton (shared per-request cache),
 * loads its migration and the public (auth-optional) route file, and registers the `feature` Gate
 * ability so authorization code can do Gate::allows('feature', 'events'). A small, self-contained
 * Platform module — reads Identity's User only for role targeting; the admin editor lives in this
 * module's Filament/Resources (auto-discovered by the panel).
 */
class FeaturesServiceProvider extends BaseDomainServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = ['routes/features.php'];

    public function register(): void
    {
        $this->app->singleton(FeatureFlagService::class);
    }

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    protected function bootDomain(): void
    {
        // Gate parity with EnsureFeatureEnabled: a platform admin always passes; otherwise the
        // flag (incl. its env override) decides. Guests arrive as null, hence the nullable type.
        Gate::define('feature', static function (?User $user, string $key): bool {
            if ($user !== null && $user->hasAnyRole(['super_admin', 'admin'])) {
                return true;
            }

            return app(FeatureFlagService::class)->isEnabled($key, $user);
        });
    }
}
