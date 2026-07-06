<?php

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for domain modules. Wires a domain's migrations, route files, and
 * policies by convention so each domain provider stays tiny. Purely structural — no domain
 * names or business logic.
 *
 * Route files load inside the framework 'api' middleware group under '/api', so a domain
 * route file only declares the version segment (e.g. Route::prefix('v1')).
 *
 * Subclasses override domainPath() and set $routeFiles / $policies.
 */
abstract class BaseDomainServiceProvider extends ServiceProvider
{
    /** Route files relative to the domain root. */
    protected array $routeFiles = [];

    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** Absolute path to the domain root (folder holding Database/, routes/, ...). */
    protected function domainPath(): ?string
    {
        return null;
    }

    public function boot(): void
    {
        $root = $this->domainPath();

        if ($root !== null) {
            $migrations = $root.'/Database/Migrations';

            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }

            $this->loadDomainRoutes($root);
        }

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        $this->bootDomain();
    }

    private function loadDomainRoutes(string $root): void
    {
        $files = array_values(array_filter(array_map(
            fn (string $file) => $root.'/'.ltrim($file, '/'),
            $this->routeFiles,
        ), 'is_file'));

        if ($files === []) {
            return;
        }

        Route::prefix('api')->middleware('api')->group(function () use ($files): void {
            foreach ($files as $path) {
                $this->loadRoutesFrom($path);
            }
        });
    }

    /** Hook for subclasses to add domain-specific boot wiring. */
    protected function bootDomain(): void
    {
        //
    }
}
