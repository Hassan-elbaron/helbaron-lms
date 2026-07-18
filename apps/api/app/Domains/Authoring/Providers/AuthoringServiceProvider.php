<?php

namespace App\Domains\Authoring\Providers;

use App\Domains\Authoring\Curriculum\CurriculumReadAdapter;
use App\Domains\Authoring\Media\LessonMediaAssetPort;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Authoring\Policies\LessonPolicy;
use App\Domains\Authoring\Policies\SectionPolicy;
use App\Domains\Authoring\Services\CurriculumPublishGuard;
use App\Domains\Catalog\Contracts\CoursePublishGuard;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Media\Contracts\MediaAssetPort;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
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

        // Authoring owns lesson media metadata; expose it to other contexts as a MediaAssetRef.
        $this->app->bind(MediaAssetPort::class, LessonMediaAssetPort::class);

        // Temporary Phase-1 curriculum read projection (enrollability + resource DTO mappers).
        $this->app->bind(CurriculumReadPort::class, CurriculumReadAdapter::class);
    }

    protected function bootDomain(): void
    {
        // The single source of truth for "may this actor manage this course's curriculum".
        // Every section/lesson policy resolves to this gate via the parent course, so ownership
        // logic lives in exactly one place.
        //
        //   1. super_admin              — global bypass (preserved).
        //   2. authoring.curriculum.manage permission — admin-level global access (preserved).
        //   3. assigned trainer         — instructor scoped to courses they train, and only while
        //                                 the course is not archived (business rule).
        Gate::define('authoring.manage-curriculum', function (Actor $user, Course $course): bool {
            // Privileged bypass mirrors InstructorController::ownedCourse — super_admin + admin are
            // authorized by role (role checks are guard-robust under Sanctum). An explicit global
            // authoring.curriculum.manage grant is also honoured for any other principal.
            if ($user->hasRole(['super_admin', 'admin']) || $user->can('authoring.curriculum.manage')) {
                return true;
            }

            return ! $course->isArchived() && $course->isTrainedBy($user->actorId());
        });
    }
}
