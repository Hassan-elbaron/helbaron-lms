<?php

namespace App\Domains\Catalog\Providers;

use App\Domains\Catalog\Contracts\CoursePublishGuard;
use App\Domains\Catalog\Contracts\NullCoursePublishGuard;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Models\CourseAnnouncement;
use App\Domains\Catalog\Policies\CategoryPolicy;
use App\Domains\Catalog\Policies\CourseAnnouncementPolicy;
use App\Domains\Catalog\Policies\CoursePolicy;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;

/**
 * Wires the Catalog module: config, migrations, public routes, policies, and the default
 * CoursePublishGuard binding (a downstream domain may override it later).
 */
class CatalogServiceProvider extends BaseDomainServiceProvider
{
    protected array $routeFiles = ['routes/catalog.php', 'routes/teach.php'];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        Course::class => CoursePolicy::class,
        Category::class => CategoryPolicy::class,
        CourseAnnouncement::class => CourseAnnouncementPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/catalog.php', 'catalog');

        // Default publish guard; standalone Catalog always allows publishing.
        $this->app->bind(CoursePublishGuard::class, NullCoursePublishGuard::class);
    }
}
