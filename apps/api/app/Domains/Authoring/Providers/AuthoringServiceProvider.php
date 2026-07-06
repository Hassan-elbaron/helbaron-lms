<?php

namespace App\Domains\Authoring\Providers;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Authoring\Policies\LessonPolicy;
use App\Domains\Authoring\Policies\SectionPolicy;
use App\Domains\Authoring\Services\CurriculumPublishGuard;
use App\Domains\Catalog\Contracts\CoursePublishGuard;
use App\Domains\Catalog\Models\Course;
use App\Domains\Identity\Models\User;
use App\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Wires the Authoring module and, crucially, binds Catalog's CoursePublishGuard to Authoring's
 * CurriculumPublishGuard — so publishing a course now validates its curriculum. This overrides
 * Catalog's default NullCoursePublishGuard binding (Authoring loads after Catalog).
 */
class AuthoringServiceProvider extends BaseDomainServiceProvider
{
    protected array $routeFiles = ['routes/authoring_admin.php'];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        Section::class => SectionPolicy::class,
        Lesson::class => LessonPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/authoring.php', 'authoring');

        // Inversion of control: curriculum validity now governs course publishing.
        $this->app->bind(CoursePublishGuard::class, CurriculumPublishGuard::class);
    }

    protected function bootDomain(): void
    {
        // Authoring 'manage' ability on a Catalog Course (used by curriculum/section endpoints).
        Gate::define('authoring.manage-curriculum', function (User $user, Course $course): bool {
            return $user->hasRole('super_admin') || $user->can('authoring.curriculum.manage');
        });
    }
}
