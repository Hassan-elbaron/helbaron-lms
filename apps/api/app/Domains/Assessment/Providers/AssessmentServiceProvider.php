<?php

namespace App\Domains\Assessment\Providers;

use App\Domains\Assessment\Enums\AssessmentPermission;
use App\Domains\Assessment\Grading\AnswerNormalizer;
use App\Domains\Assessment\Grading\GraderRegistry;
use App\Domains\Assessment\Grading\Graders\FillInBlankGrader;
use App\Domains\Assessment\Grading\Graders\MultipleChoiceGrader;
use App\Domains\Assessment\Grading\Graders\ShortAnswerGrader;
use App\Domains\Assessment\Grading\Graders\SingleChoiceGrader;
use App\Domains\Assessment\Grading\Graders\TrueFalseGrader;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Policies\AssessmentPolicy;
use App\Domains\Assessment\Support\LessonAssessmentAdapter;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Identity\Contracts\CourseAccessPort;
use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Gate;

class AssessmentServiceProvider extends BaseDomainServiceProvider
{
    /** @var list<string> */
    protected array $routeFiles = [
        'routes/assessment_admin.php',
        'routes/assessment_learner.php',
    ];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        Assessment::class => AssessmentPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        // Assessment owns the lesson↔assessment contract; Authoring consumes it without ever
        // importing an Assessment class.
        $this->app->bind(LessonAssessmentPort::class, LessonAssessmentAdapter::class);

        // The grader registry is the single extension point for question types. Adding a type is:
        // add the enum case, write a grader, register it on the line below. Nothing else changes.
        $this->app->singleton(GraderRegistry::class, function ($app): GraderRegistry {
            $normalizer = $app->make(AnswerNormalizer::class);

            return new GraderRegistry([
                new SingleChoiceGrader,
                new TrueFalseGrader,
                new MultipleChoiceGrader,
                new ShortAnswerGrader($normalizer),
                new FillInBlankGrader($normalizer),
            ]);
        });
    }

    protected function bootDomain(): void
    {
        // Single source of truth for "may this actor manage this assessment".
        //
        //   1. super_admin / admin  — role bypass, matching the Authoring gate (role checks are
        //                             guard-robust under Sanctum, permission checks are not).
        //   2. assessment.manage    — explicit global grant for any other privileged principal.
        //   3. course ownership     — an instructor may manage the assessments of a course they
        //                             train, delegated to CourseAccessPort so this domain never
        //                             touches the Course model.
        //
        // A course-less assessment (a future platform-wide bank) is admin-only by construction:
        // rule 3 cannot apply, so nobody reaches it through ownership.
        //
        // NOTE the gate name must NOT equal the permission name. `$user->can('x')` consults gates
        // before Spatie permissions, so a gate called `assessment.manage` checking the permission
        // `assessment.manage` re-enters itself without the model argument and fatals. Authoring
        // avoids this the same way: gate `authoring.manage-curriculum`, permission
        // `authoring.curriculum.manage`.
        Gate::define('assessment.manage-assessment', function (Actor $user, Assessment $assessment): bool {
            if ($user->hasRole(['super_admin', 'admin']) || $user->can(AssessmentPermission::Manage->value)) {
                return true;
            }

            $courseId = $assessment->course_id;

            return $courseId !== null
                && app(CourseAccessPort::class)->canManageContent($user, (int) $courseId);
        });
    }
}
