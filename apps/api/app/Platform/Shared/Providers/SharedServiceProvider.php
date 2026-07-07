<?php

namespace App\Platform\Shared\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the shared foundation: merges shared/features config and installs reusable
 * schema Blueprint macros (publicId, auditColumns, seoColumns) so every domain migration
 * expresses the shared conventions consistently.
 *
 * No business logic and no domain registration lives here.
 */
class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/shared.php', 'shared');
        $this->mergeConfigFrom(__DIR__.'/../../../config/features.php', 'features');
    }

    public function boot(): void
    {
        $this->registerBlueprintMacros();
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
