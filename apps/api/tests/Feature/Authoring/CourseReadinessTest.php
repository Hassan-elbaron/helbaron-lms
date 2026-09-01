<?php

use App\Domains\Assessment\Models\Assessment;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Block;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\LessonMedia;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Contracts\CoursePublishGuard;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Commerce\Contracts\PurchaseSummaryPort;
use App\Platform\Shared\Publishing\Data\CourseReadinessInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(IdentitySeeder::class);
});

/** A course that passes every rule, so each test can break exactly one thing. */
function readyCourse(): array
{
    $course = Course::factory()->create([
        'status' => CourseStatus::Draft,
        'description' => 'A real description.',
        'thumbnail_path' => 'courses/thumb.jpg',
    ]);

    $instructor = User::factory()->create();
    $instructor->assignRole(SpatieRole::findByName('instructor', 'web'));
    $course->syncTrainers([$instructor->id]);

    $section = Section::factory()->create([
        'course_id' => $course->id,
        'publish_state' => PublishState::Published->value,
    ]);

    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => ['html' => '<p>Body</p>'],
    ]);

    return [$course, $instructor, $section];
}

function readinessInput(Course $course): CourseReadinessInput
{
    return new CourseReadinessInput(
        courseId: (int) $course->getKey(),
        coursePublicId: (string) $course->getAttribute('public_id'),
        description: $course->getAttribute('description'),
        thumbnailPath: $course->getAttribute('thumbnail_path'),
        hasInstructor: $course->trainerLinks()->exists(),
        visibility: $course->getAttribute('visibility')?->value,
    );
}

/** Build the readiness input the way CurriculumPublishGuard does, including the acquisition facts. */
function readinessInputFromModel(Course $course): CourseReadinessInput
{
    return new CourseReadinessInput(
        courseId: (int) $course->getKey(),
        coursePublicId: (string) $course->getAttribute('public_id'),
        description: $course->getAttribute('description'),
        thumbnailPath: $course->getAttribute('thumbnail_path'),
        hasInstructor: $course->trainerLinks()->exists(),
        visibility: $course->getAttribute('visibility')?->value,
        isFree: (bool) $course->getAttribute('is_free'),
        isSoldByActiveProduct: app(PurchaseSummaryPort::class)
            ->forCourse((int) $course->getKey())->purchasable,
    );
}

function reportFor(Course $course)
{
    return app(CoursePublishGuard::class)->report(readinessInput($course));
}

it('reports a fully prepared course as publishable with a perfect score', function () {
    [$course] = readyCourse();

    $report = reportFor($course);

    expect($report->isPublishable())->toBeTrue()
        ->and($report->blockers())->toBeEmpty()
        ->and($report->warnings())->toBeEmpty()
        ->and($report->score())->toBe(100);
});

it('blocks a course with no sections and stops there', function () {
    $course = Course::factory()->create(['description' => 'x', 'thumbnail_path' => 'x.jpg']);

    $report = reportFor($course);

    expect($report->isPublishable())->toBeFalse();

    // One cause, one issue: the lesson rules are not reported as separate failures when the real
    // problem is that there is nowhere for a lesson to live.
    $codes = array_map(fn ($i) => $i->code, $report->blockers());
    expect($codes)->toBe(['course.no_sections']);
});

it('blocks a course whose lessons are all drafts', function () {
    [$course, , $section] = readyCourse();
    Lesson::where('section_id', $section->id)->update(['publish_state' => PublishState::Draft->value]);

    $report = reportFor($course);

    expect($report->isPublishable())->toBeFalse()
        ->and(array_map(fn ($i) => $i->code, $report->blockers()))->toContain('course.no_published_lesson');
});

it('blocks a published lesson that has neither content nor media', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'title' => 'Hollow lesson',
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);

    $report = reportFor($course);
    $blocker = collect($report->blockers())->firstWhere('code', 'lesson.empty_content');

    expect($report->isPublishable())->toBeFalse()
        ->and($blocker)->not->toBeNull()
        ->and($blocker->title)->toContain('Hollow lesson')
        ->and($blocker->entityType)->toBe('lesson');
});

it('leaves an empty DRAFT lesson alone', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Draft->value,
        'content' => null,
    ]);

    // Unfinished work parked in draft is the point of draft, not a publishing defect — and it is
    // not even worth warning about.
    $report = reportFor($course);

    expect($report->isPublishable())->toBeTrue()
        ->and(array_map(fn ($i) => $i->code, $report->warnings()))->not->toContain('lesson.empty_content');
});

it('blocks a quiz lesson with no assessment attached', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'title' => 'Module check',
        'type' => LessonType::Quiz->value,
        'publish_state' => PublishState::Published->value,
        'assessment_id' => null,
    ]);

    $blocker = collect(reportFor($course)->blockers())->firstWhere('code', 'lesson.quiz_without_published_assessment');

    expect($blocker)->not->toBeNull()
        ->and($blocker->explanation)->toContain('No quiz is attached');
});

it('blocks a quiz lesson whose assessment is still a draft', function () {
    [$course, , $section] = readyCourse();
    $assessment = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'draft']);
    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Quiz->value,
        'publish_state' => PublishState::Published->value,
        'assessment_id' => $assessment->id,
    ]);

    // Matches what the learner would actually see: the publish-gated reference resolves to null,
    // so the lesson renders as unavailable.
    $blocker = collect(reportFor($course)->blockers())->firstWhere('code', 'lesson.quiz_without_published_assessment');

    expect($blocker)->not->toBeNull()
        ->and($blocker->explanation)->toContain('still a draft');
});

it('accepts a quiz lesson with a published assessment', function () {
    [$course, , $section] = readyCourse();
    $assessment = Assessment::factory()->create(['course_id' => $course->id, 'status' => 'published']);
    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Quiz->value,
        'publish_state' => PublishState::Published->value,
        'assessment_id' => $assessment->id,
    ]);

    expect(reportFor($course)->isPublishable())->toBeTrue();
});

it('warns about thin metadata without blocking', function () {
    [$course] = readyCourse();
    $course->forceFill(['description' => '', 'thumbnail_path' => null])->save();

    $report = reportFor($course);
    $codes = array_map(fn ($i) => $i->code, $report->warnings());

    expect($report->isPublishable())->toBeTrue()
        ->and($codes)->toContain('course.missing_description')
        ->and($codes)->toContain('course.missing_thumbnail')
        ->and($report->score())->toBeLessThan(100);
});

it('warns when no instructor is assigned', function () {
    [$course, $instructor] = readyCourse();
    $course->syncTrainers([]);

    $report = reportFor($course);

    expect($report->isPublishable())->toBeTrue()
        ->and(array_map(fn ($i) => $i->code, $report->warnings()))->toContain('course.no_instructor');
});

it('keeps the guard verdict identical to the report it exposes', function () {
    [$course, , $section] = readyCourse();
    Lesson::where('section_id', $section->id)->update(['publish_state' => PublishState::Draft->value]);

    $guard = app(CoursePublishGuard::class);

    // The whole point of the shared report: a panel saying "ready" while publish refuses would be
    // worse than no panel at all.
    $verdict = $guard->canPublish($course);
    $report = $guard->report(readinessInput($course));

    expect($verdict)->toBe($report->isPublishable())
        ->and($guard->reason())->toBe($report->firstBlockerReason());
});

it('exposes readiness to the owning instructor over the API', function () {
    [$course, $instructor] = readyCourse();

    $this->actingAs($instructor, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}/readiness")
        ->assertOk()
        ->assertJsonPath('data.is_publishable', true)
        ->assertJsonPath('data.score', 100)
        ->assertJsonStructure([
            'data' => ['is_publishable', 'score', 'evaluated_at', 'blockers', 'warnings', 'passed_checks'],
        ]);
});

it('returns every issue field the panel needs to act on', function () {
    [$course, $instructor, $section] = readyCourse();
    Lesson::where('section_id', $section->id)->update(['publish_state' => PublishState::Draft->value]);

    $blocker = $this->actingAs($instructor, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}/readiness")
        ->assertOk()
        ->json('data.blockers.0');

    expect(array_keys($blocker))->toEqualCanonicalizing(
        ['code', 'severity', 'title', 'explanation', 'recommended_action', 'entity_type', 'entity_id'],
    );
});

it('hides readiness for a course the caller does not train', function () {
    [$course] = readyCourse();
    $stranger = User::factory()->create();
    $stranger->assignRole(SpatieRole::findByName('instructor', 'web'));

    // 404 not 403, matching every other instructor-portal route: non-owned is indistinguishable
    // from missing, so an instructor cannot probe for courses they do not train.
    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}/readiness")
        ->assertNotFound();
});

it('refuses readiness to a learner', function () {
    [$course] = readyCourse();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}/readiness")
        ->assertForbidden();
});

it('refuses the real publish when readiness reports a blocker', function () {
    [$course, $instructor, $section] = readyCourse();
    Lesson::where('section_id', $section->id)->update(['publish_state' => PublishState::Draft->value]);

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/teach/courses/{$course->public_id}/publish")
        ->assertStatus(422);

    expect($course->refresh()->status)->toBe(CourseStatus::Draft);
});

// ---------------------------------------------------------------- visibility

it('passes visibility when the course is public', function () {
    [$course] = readyCourse();

    expect(reportFor($course)->passedChecks)->toContain('course.not_publicly_visible');
});

it('warns without blocking when a course is not publicly visible', function (string $visibility) {
    [$course] = readyCourse();
    $course->forceFill(['visibility' => $visibility])->save();

    $report = reportFor($course->refresh());

    // Private and unlisted courses are a legitimate way to ship — internal or cohort-gated content
    // is published on purpose. Blocking would break that workflow and would retroactively stop
    // every already-published non-public course from re-publishing.
    expect($report->isPublishable())->toBeTrue()
        ->and(array_column($report->warnings(), 'code'))->toContain('course.not_publicly_visible')
        ->and(array_column($report->blockers(), 'code'))->not->toContain('course.not_publicly_visible');
})->with(['private', 'unlisted', 'hidden']);

it('names the offending visibility so the author can act on it', function () {
    [$course] = readyCourse();
    $course->forceFill(['visibility' => 'private'])->save();

    $issue = collect(reportFor($course->refresh())->warnings())
        ->firstWhere('code', 'course.not_publicly_visible');

    expect($issue->title)->toContain('private')
        ->and($issue->recommendedAction)->not->toBeEmpty()
        ->and($issue->entityType)->toBe('course')
        ->and($issue->entityPublicId)->toBe($course->public_id);
});

it('reports an unrecognised visibility separately rather than ignoring it', function (?string $visibility) {
    [$course] = readyCourse();

    // Exercised at the DTO, not through Eloquent, and deliberately so: Course casts `visibility` to
    // the Visibility enum, so an unknown value cannot survive a save — the model layer already
    // makes that state unreachable. What IS reachable is a CALLER handing the evaluator a value the
    // enum does not know, including the null default on the input DTO. That is the case this rule
    // exists for, and this is where it can actually be reached.
    $report = app(CoursePublishGuard::class)->report(new CourseReadinessInput(
        courseId: (int) $course->getKey(),
        coursePublicId: (string) $course->getAttribute('public_id'),
        description: $course->getAttribute('description'),
        thumbnailPath: $course->getAttribute('thumbnail_path'),
        hasInstructor: true,
        visibility: $visibility,
    ));

    expect(array_column($report->warnings(), 'code'))->toContain('course.invalid_visibility')
        ->and($report->isPublishable())->toBeTrue();
})->with(['sideways', '', null]);

it('still allows the real publish for a private course', function () {
    [$course, $instructor] = readyCourse();
    $course->forceFill(['visibility' => 'private'])->save();

    $this->actingAs($instructor, 'sanctum')
        ->postJson("/api/v1/teach/courses/{$course->public_id}/publish")
        ->assertOk();

    expect($course->refresh()->status)->toBe(CourseStatus::Published);
});

it('surfaces the visibility warning through the readiness endpoint', function () {
    [$course, $instructor] = readyCourse();
    $course->forceFill(['visibility' => 'private'])->save();

    $body = $this->actingAs($instructor, 'sanctum')
        ->getJson("/api/v1/teach/courses/{$course->public_id}/readiness")
        ->assertOk()
        ->json('data');

    expect(json_encode($body))->toContain('course.not_publicly_visible');
});

/*
 * ── A3: substance that is not text ──────────────────────────────────────────────────────────────
 *
 * `lesson.empty_content` was promoted to a Blocker while hasMeaningfulContent() still applied
 * trim(strip_tags()). A lesson whose body is only an embedded video strips to '' and was declared
 * empty, so the single most common authoring shape in this product could not be published — and the
 * author was told the lesson had "neither content nor media" while looking at their video.
 *
 * The lesson-level consequences are asserted here; the payload predicate's own matrix (nested
 * arrays, reference keys, metadata-only stubs) is in tests/Unit/Authoring/LessonContentSubstanceTest.
 */
it('publishes a course whose lesson holds only an embedded video', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'title' => 'Recorded walkthrough',
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => ['html' => '<iframe src="https://player.example/abc" allowfullscreen></iframe>'],
    ]);

    $report = reportFor($course);

    expect($report->isPublishable())->toBeTrue()
        ->and(array_map(fn ($i) => $i->code, $report->blockers()))->not->toContain('lesson.empty_content');
});

it('publishes a course whose lesson holds only an image', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => ['html' => '<img src="/media/infographic.png" alt="">'],
    ]);

    expect(reportFor($course)->isPublishable())->toBeTrue();
});

it('publishes a course whose lesson carries only a structured media reference', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        // No string leaf anywhere — the old recursion counted strings only and called this empty.
        'content' => ['embed' => ['media_id' => 12]],
    ]);

    expect(reportFor($course)->isPublishable())->toBeTrue();
});

it('counts a published content block as substance for an otherwise empty lesson', function () {
    [$course, , $section] = readyCourse();
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);
    Block::factory()->published()->create([
        'lesson_id' => $lesson->id,
        'payload' => ['html' => '<p>Block body</p>'],
    ]);

    expect(reportFor($course)->isPublishable())->toBeTrue();
});

it('does not count a DRAFT content block as substance', function () {
    [$course, , $section] = readyCourse();
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'title' => 'Only a draft block',
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);
    // A learner never receives a draft block, so it cannot be what makes the lesson non-empty.
    Block::factory()->create([
        'lesson_id' => $lesson->id,
        'payload' => ['html' => '<p>Not published yet</p>'],
    ]);

    // A11.3: assert WHICH blocker fired. `isPublishable() === false` alone goes green for the wrong
    // reason the moment any unrelated rule regresses, which is worse than no assertion at all.
    $blocker = collect(reportFor($course)->blockers())->firstWhere('code', 'lesson.empty_content');

    expect($blocker)->not->toBeNull()
        ->and($blocker->title)->toContain('Only a draft block');
});

/*
 * NOTE — there is deliberately no "archived block" test here.
 *
 * The Round 2 review listed `PublishState::Archived` as an untested branch, but that state does not
 * exist: PublishState has exactly two cases, Draft and Published. Draft is therefore the only
 * not-published state a block can be in, and it is covered directly above. A test asserting on a
 * non-existent enum case would not compile, and inventing the case to satisfy the review would add a
 * state the product does not have.
 */

it('counts attached media as substance for a lesson with no content at all', function () {
    [$course, , $section] = readyCourse();
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Video->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);
    // The `$lesson->media !== null` branch had NO readiness test at all, despite being the shape of
    // every video lesson in the product: the media row IS the lesson.
    LessonMedia::factory()->create(['lesson_id' => $lesson->id]);

    $report = reportFor($course);

    expect($report->isPublishable())->toBeTrue()
        ->and(array_map(fn ($i) => $i->code, $report->blockers()))->not->toContain('lesson.empty_content');
});

it('counts localized block content as substance', function () {
    [$course, , $section] = readyCourse();
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);
    // The content_i18n path was never exercised. A block whose substance lives only in the localized
    // surface is a real authoring shape in a bilingual product.
    Block::factory()->published()->create([
        'lesson_id' => $lesson->id,
        'payload' => [],
        'content_i18n' => ['en' => ['html' => '<p>Body</p>'], 'ar' => ['html' => '<p>نص</p>']],
    ]);

    expect(reportFor($course)->isPublishable())->toBeTrue();
});

it('does not count an empty localized block surface as substance', function () {
    [$course, , $section] = readyCourse();
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'title' => 'Blank translations',
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);
    Block::factory()->published()->create([
        'lesson_id' => $lesson->id,
        'payload' => [],
        'content_i18n' => ['en' => ['html' => '<p>&nbsp;</p>'], 'ar' => ['html' => '']],
    ]);

    $blocker = collect(reportFor($course)->blockers())->firstWhere('code', 'lesson.empty_content');

    expect($blocker)->not->toBeNull()
        ->and($blocker->title)->toContain('Blank translations');
});

it('does not count a metadata-only block payload as substance', function () {
    [$course, , $section] = readyCourse();
    $lesson = Lesson::factory()->create([
        'section_id' => $section->id,
        'title' => 'Stub block lesson',
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);
    // A block the author created and never filled in. "text" is a non-empty string, so the old
    // string-leaf recursion counted it and let a hollow lesson publish.
    Block::factory()->published()->create([
        'lesson_id' => $lesson->id,
        'payload' => ['type' => 'text'],
    ]);

    $blocker = collect(reportFor($course)->blockers())->firstWhere('code', 'lesson.empty_content');

    expect($blocker)->not->toBeNull()
        ->and($blocker->title)->toContain('Stub block lesson');
});

it('explains every kind of substance that would satisfy the empty-lesson blocker', function () {
    [$course, , $section] = readyCourse();
    Lesson::factory()->create([
        'section_id' => $section->id,
        'type' => LessonType::Article->value,
        'publish_state' => PublishState::Published->value,
        'content' => null,
    ]);

    $blocker = collect(reportFor($course)->blockers())->firstWhere('code', 'lesson.empty_content');

    // The old wording ("It has neither content nor media") was already wrong — blocks and quizzes
    // counted — and would have flatly contradicted an author looking at an embedded video.
    expect($blocker->explanation)->toContain('embedded media')
        ->and($blocker->explanation)->toContain('content block')
        ->and($blocker->explanation)->toContain('quiz')
        ->and($blocker->recommendedAction)->toContain('embed');
});

/*
 * ── A5: a course nobody can acquire ─────────────────────────────────────────────────────────────
 *
 * `courses.is_free` is fail-closed (defaults to false), which is right — a course must not be
 * giveable-away until somebody says so. But it means an author can publish a course that renders
 * "Not available yet" to every visitor and whose enrol endpoint refuses everyone, with nothing
 * telling them why. This rule tells them.
 *
 * WARNING, never a blocker: both remedies live outside the Course Builder, nobody already enrolled
 * is affected, and blocking would repeat the A3 mistake of refusing perfectly valid content.
 */
function acquisitionInput(Course $course, ?bool $isFree, ?bool $isSold): CourseReadinessInput
{
    return new CourseReadinessInput(
        courseId: (int) $course->getKey(),
        coursePublicId: (string) $course->getAttribute('public_id'),
        description: $course->getAttribute('description'),
        thumbnailPath: $course->getAttribute('thumbnail_path'),
        hasInstructor: $course->trainerLinks()->exists(),
        visibility: $course->getAttribute('visibility')?->value,
        isFree: $isFree,
        isSoldByActiveProduct: $isSold,
    );
}

it('warns without blocking when a course is neither free nor sold', function () {
    [$course] = readyCourse();

    $report = app(CoursePublishGuard::class)->report(acquisitionInput($course, false, false));
    $warning = collect($report->warnings())->firstWhere('code', 'course.not_acquirable');

    expect($report->isPublishable())->toBeTrue()
        ->and($warning)->not->toBeNull()
        ->and(array_map(fn ($i) => $i->code, $report->blockers()))->not->toContain('course.not_acquirable');
});

it('tells the author both ways out of the not-acquirable state', function () {
    [$course] = readyCourse();

    $warning = collect(app(CoursePublishGuard::class)->report(acquisitionInput($course, false, false))->warnings())
        ->firstWhere('code', 'course.not_acquirable');

    // An issue an author cannot act on is a bug in the rule, so both remedies must be named.
    expect($warning->recommendedAction)->toContain('Free course')
        ->and($warning->recommendedAction)->toContain('product')
        ->and($warning->explanation)->toContain('Not available yet');
});

it('does not warn when the course is declared free', function () {
    [$course] = readyCourse();

    $report = app(CoursePublishGuard::class)->report(acquisitionInput($course, true, false));

    expect(array_map(fn ($i) => $i->code, $report->warnings()))->not->toContain('course.not_acquirable')
        ->and($report->passedChecks)->toContain('course.not_acquirable');
});

it('does not warn when an active product sells the course', function () {
    [$course] = readyCourse();

    $report = app(CoursePublishGuard::class)->report(acquisitionInput($course, false, true));

    expect(array_map(fn ($i) => $i->code, $report->warnings()))->not->toContain('course.not_acquirable')
        ->and($report->passedChecks)->toContain('course.not_acquirable');
});

it('skips the acquisition rule entirely when the caller supplies neither fact', function () {
    [$course] = readyCourse();

    // An older caller that has not been taught to pass these must not start reporting a phantom
    // issue — nor claim the check passed when it was never evaluated.
    $report = app(CoursePublishGuard::class)->report(acquisitionInput($course, null, null));

    expect(array_map(fn ($i) => $i->code, $report->warnings()))->not->toContain('course.not_acquirable')
        ->and($report->passedChecks)->not->toContain('course.not_acquirable');
});

it('warns through the real publish guard for a course with no product and no free flag', function () {
    [$course] = readyCourse();

    // The guard resolves both facts itself (is_free from the model, sold-ness through the Shared
    // purchase-summary port), so this exercises the wiring rather than a hand-built input.
    $report = app(CoursePublishGuard::class)->report(readinessInputFromModel($course));

    expect($report->isPublishable())->toBeTrue()
        ->and(array_map(fn ($i) => $i->code, $report->warnings()))->toContain('course.not_acquirable');
});

it('stops warning through the real publish guard once the course is declared free', function () {
    [$course] = readyCourse();
    $course->forceFill(['is_free' => true])->save();

    $report = app(CoursePublishGuard::class)->report(readinessInputFromModel($course->refresh()));

    expect(array_map(fn ($i) => $i->code, $report->warnings()))->not->toContain('course.not_acquirable');
});
