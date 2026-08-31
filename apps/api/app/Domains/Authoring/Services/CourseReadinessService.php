<?php

namespace App\Domains\Authoring\Services;

use App\Domains\Authoring\Models\Block;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use App\Platform\Shared\Enums\Visibility;
use App\Platform\Shared\Publishing\Data\CourseReadinessInput;
use App\Platform\Shared\Publishing\Data\ReadinessIssue;
use App\Platform\Shared\Publishing\Data\ReadinessReport;
use App\Platform\Shared\Publishing\Enums\ReadinessSeverity;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Evaluates whether a course is fit to publish, and explains itself.
 *
 * This is the only place course publish rules live. The publish guard reads its verdict from the
 * report produced here rather than running a parallel check, so the readiness panel an author sees
 * and the guard that refuses their publish can never disagree.
 *
 * Course facts arrive as a flat CourseReadinessInput rather than a Course model: Authoring may not
 * depend on Catalog, and Catalog owns the mapping. Curriculum is read from Authoring's own models.
 *
 * Every rule answers three questions for the author: what is wrong, why it matters, and what to do
 * about it — plus which entity to open. An issue the author cannot act on is a bug in the rule.
 *
 * On severity: a blocker must describe something that is genuinely broken for a learner AND that
 * could not already be true of published content, because adding a blocker retroactively prevents
 * existing courses from re-publishing. When in doubt, warn.
 */
class CourseReadinessService extends BaseService
{
    public function __construct(private readonly LessonAssessmentPort $assessments) {}

    public function evaluate(CourseReadinessInput $course): ReadinessReport
    {
        /** @var list<ReadinessIssue> $issues */
        $issues = [];
        /** @var list<string> $passed */
        $passed = [];

        $this->checkMetadata($course, $issues, $passed);

        $sectionIds = Section::where('course_id', $course->courseId)->pluck('id');

        if ($sectionIds->isEmpty()) {
            $issues[] = new ReadinessIssue(
                code: 'course.no_sections',
                severity: ReadinessSeverity::Blocker,
                title: 'The course has no sections.',
                explanation: 'A course needs at least one section before learners have anything to open.',
                recommendedAction: 'Add a section in the Course Builder.',
                entityType: 'course',
                entityPublicId: $course->coursePublicId,
            );

            // Every remaining rule reads lessons, which cannot exist without a section. Returning
            // here avoids reporting a cascade of consequences that all have the same single cause.
            return new ReadinessReport($issues, $passed, now()->toIso8601String());
        }

        $passed[] = 'course.no_sections';

        /** @var Collection<int, Lesson> $lessons */
        $lessons = Lesson::whereIn('section_id', $sectionIds)
            ->with([
                'media:id,lesson_id',
                'blocks:id,lesson_id,payload,content_i18n,publish_state,position',
            ])
            ->get();

        $this->checkPublishedLessons($lessons, $course, $issues, $passed);
        $this->checkLessonContent($lessons, $issues, $passed);
        $this->checkQuizAssessments($lessons, $issues, $passed);

        return new ReadinessReport($issues, $passed, now()->toIso8601String());
    }

    /**
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passed
     */
    private function checkMetadata(CourseReadinessInput $course, array &$issues, array &$passed): void
    {
        // Warnings, not blockers: a thin listing page is a marketing problem, not a broken course.
        // The title is not checked because the column is non-nullable — a rule that can never fire
        // is noise in the panel.
        if (trim((string) $course->description) === '') {
            $issues[] = new ReadinessIssue(
                code: 'course.missing_description',
                severity: ReadinessSeverity::Warning,
                title: 'The course has no description.',
                explanation: 'The description is what prospective learners read on the catalog page.',
                recommendedAction: 'Add a description in course settings.',
                entityType: 'course',
                entityPublicId: $course->coursePublicId,
            );
        } else {
            $passed[] = 'course.missing_description';
        }

        if (trim((string) $course->thumbnailPath) === '') {
            $issues[] = new ReadinessIssue(
                code: 'course.missing_thumbnail',
                severity: ReadinessSeverity::Warning,
                title: 'The course has no thumbnail.',
                explanation: 'Courses without an image are noticeably weaker in catalog listings.',
                recommendedAction: 'Upload a thumbnail in course settings.',
                entityType: 'course',
                entityPublicId: $course->coursePublicId,
            );
        } else {
            $passed[] = 'course.missing_thumbnail';
        }

        if (! $course->hasInstructor) {
            $issues[] = new ReadinessIssue(
                code: 'course.no_instructor',
                severity: ReadinessSeverity::Warning,
                title: 'No instructor is assigned to this course.',
                explanation: 'The course page will show no one teaching it, and it will not appear on any trainer profile.',
                recommendedAction: 'Assign at least one instructor to the course.',
                entityType: 'course',
                entityPublicId: $course->coursePublicId,
            );
        } else {
            $passed[] = 'course.no_instructor';
        }

        $this->checkVisibility($course, $issues, $passed);
        $this->checkAcquisition($course, $issues, $passed);
    }

    /**
     * Publishing a course does not put it in the catalog — the catalog listing also requires
     * `visibility = public` (Course::scopeVisible). An author who publishes a private course and
     * then cannot find it has hit exactly this, and today nothing tells them why.
     *
     * WARNING, not a blocker, on two grounds. Private and unlisted courses are a legitimate
     * shipping mode — internal, cohort-gated, or link-shared content is published on purpose — so
     * refusing the publish would break a real workflow. And promoting it to a blocker would
     * retroactively stop every already-published non-public course from re-publishing, which the
     * severity policy on ReadinessSeverity explicitly rules out.
     *
     * An unrecognised value is reported too rather than ignored: the column is a plain string with
     * a default, so a bad write is possible, and silently treating it as fine would hide it.
     *
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passed
     */
    private function checkVisibility(CourseReadinessInput $course, array &$issues, array &$passed): void
    {
        $visibility = $course->visibility;

        if ($visibility !== null && Visibility::tryFrom($visibility)?->isPublic() === true) {
            $passed[] = 'course.not_publicly_visible';

            return;
        }

        $known = $visibility !== null && Visibility::tryFrom($visibility) !== null;

        $issues[] = new ReadinessIssue(
            code: $known ? 'course.not_publicly_visible' : 'course.invalid_visibility',
            severity: ReadinessSeverity::Warning,
            title: $known
                ? sprintf('The course visibility is "%s", so it will not appear in the catalog.', $visibility)
                : 'The course visibility is not set to a recognised value.',
            explanation: $known
                ? 'Publishing makes the course available, but only public courses are listed in the catalog. Learners will need a direct link or an enrollment you create for them.'
                : 'The catalog lists only courses marked public, and an unrecognised value is treated as not public.',
            recommendedAction: $known
                ? 'Set visibility to public in course settings if you intend learners to find it by browsing.'
                : sprintf('Set visibility in course settings to one of: %s.', implode(', ', Visibility::values())),
            entityType: 'course',
            entityPublicId: $course->coursePublicId,
        );
    }

    /**
     * A course nobody can acquire: not declared free, and not sold by any active product.
     *
     * `courses.is_free` defaults to FALSE, which is the right fail-closed default — a course must not
     * be giveable-away until somebody says so — but it means an author can publish a course that
     * renders "Not available yet" to every visitor, with nothing anywhere telling them why. This is
     * the rule that tells them.
     *
     * WARNING, not a blocker, deliberately. Both remedies live outside the Course Builder (tick
     * "free" in course settings, or attach the course to a product in Commerce), and a course in this
     * state is not broken for anyone already enrolled — it simply cannot be acquired by anyone new.
     * Blocking here would also repeat the A3 mistake of refusing to publish content that is perfectly
     * valid, which the severity policy on ReadinessSeverity rules out.
     *
     * Skipped entirely when the caller did not supply the facts (both null), so an older caller that
     * has not been taught to pass them does not start reporting a phantom issue.
     *
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passed
     */
    private function checkAcquisition(CourseReadinessInput $course, array &$issues, array &$passed): void
    {
        if ($course->isFree === null && $course->isSoldByActiveProduct === null) {
            return;
        }

        if ($course->isFree === true || $course->isSoldByActiveProduct === true) {
            $passed[] = 'course.not_acquirable';

            return;
        }

        $issues[] = new ReadinessIssue(
            code: 'course.not_acquirable',
            severity: ReadinessSeverity::Warning,
            title: 'Nobody can enroll in this course yet.',
            explanation: 'The course is not marked free and no active product sells it, so every visitor sees "Not available yet" and the enrollment endpoint refuses them.',
            recommendedAction: 'Tick "Free course" in course settings to let learners enroll at no charge, or attach the course to an active product so it can be bought.',
            entityType: 'course',
            entityPublicId: $course->coursePublicId,
        );
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passed
     */
    private function checkPublishedLessons(Collection $lessons, CourseReadinessInput $course, array &$issues, array &$passed): void
    {
        if (! config('authoring.publish.require_published_lesson', true)) {
            return;
        }

        if ($lessons->contains(fn (Lesson $l) => $l->publish_state->isPublished())) {
            $passed[] = 'course.no_published_lesson';

            return;
        }

        $issues[] = new ReadinessIssue(
            code: 'course.no_published_lesson',
            severity: ReadinessSeverity::Blocker,
            title: 'The course has no published lessons.',
            explanation: 'Draft lessons are invisible to learners, so an enrolled learner would see an empty course.',
            recommendedAction: 'Publish at least one lesson in the Course Builder.',
            entityType: 'course',
            entityPublicId: $course->coursePublicId,
        );
    }

    /**
     * A published lesson carrying neither content nor media is a dead end for a learner.
     *
     * This is a blocker: a published course must not advertise a lesson that renders an empty player
     * and can still be marked complete. Legacy lesson content, published first-class blocks, media,
     * embedded media elements, structured media references, and quiz assessments are all recognized
     * as valid substance.
     *
     * Draft lessons are skipped entirely; unfinished work parked in draft is the point of draft.
     *
     * ── WHY THIS BLOCKER IS EXEMPT FROM THE NO-RETROACTIVE-BLOCKERS POLICY ──
     *
     * checkVisibility() above declines to block partly because "promoting it to a blocker would
     * retroactively stop every already-published non-public course from re-publishing, which the
     * severity policy on ReadinessSeverity explicitly rules out". That reasoning is answered here
     * rather than left as a contradiction, and the answer is deliberate:
     *
     *   1. The two cases are not alike. A private published course is CORRECT — private and unlisted
     *      are legitimate shipping modes, so blocking them breaks a working product. A published
     *      lesson with nothing in it is not a shipping mode; it is a learner opening a blank page
     *      and being allowed to mark it complete. There is no workflow to preserve.
     *   2. The set of courses this can retroactively block is now much smaller than the policy
     *      assumed. hasMeaningfulContent() previously read text only, so an embed-only lesson — the
     *      most common shape in this product — tripped it. With embeds, media references, published
     *      blocks and quizzes all counted, what remains is a lesson that is genuinely, verifiably
     *      empty. Widening the rule can only ever UN-block a course that this check blocked before.
     *   3. It is self-service, and now says so. The issue names the lesson, and the explanation
     *      lists every kind of substance that would satisfy it, so an author who hits it can fix it
     *      in a minute — unlike a rule about the course's visibility mode, which asks them to change
     *      what they are shipping.
     *
     * No grandfathering table is therefore introduced: a data structure recording which courses are
     * exempt would outlive the reason for it, and would have to be maintained by whoever next
     * touches these rules. If this judgement is ever revisited, the cheaper lever is downgrading
     * this one issue to a Warning — not per-course exemptions.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passed
     */
    private function checkLessonContent(Collection $lessons, array &$issues, array &$passed): void
    {
        $empty = $lessons->filter(function (Lesson $lesson): bool {
            if (! $lesson->publish_state->isPublished()) {
                return false;
            }

            // Quiz lessons carry their substance in the linked assessment, not in `content`, and are
            // covered by their own rule below. Checking them here would report every quiz twice.
            if ($lesson->type->usesAssessment()) {
                return false;
            }

            if ($lesson->media !== null || $this->hasMeaningfulContent($lesson->content)) {
                return false;
            }

            return ! $lesson->blocks->contains(
                fn (Block $block): bool => $block->isPublished()
                    && ($this->hasMeaningfulContent($block->payload)
                        || $this->hasMeaningfulContent($block->content_i18n)),
            );
        });

        if ($empty->isEmpty()) {
            $passed[] = 'lesson.empty_content';

            return;
        }

        foreach ($empty as $lesson) {
            $issues[] = new ReadinessIssue(
                code: 'lesson.empty_content',
                severity: ReadinessSeverity::Blocker,
                title: sprintf('The lesson "%s" is published but empty.', $lesson->title),
                explanation: 'The lesson has no text, no embedded media, no published content block, no attached media and no quiz, so a learner who opens it sees an empty page.',
                recommendedAction: 'Add text, embed or attach media, publish a content block, or attach a quiz — or return the lesson to draft until it is ready.',
                entityType: 'lesson',
                entityPublicId: $lesson->public_id,
            );
        }
    }

    /**
     * HTML elements that ARE the lesson when they are the only thing in it.
     *
     * strip_tags() reduces a lesson whose body is a single embedded video to the empty string, so a
     * text-only test declares it empty. Once this check became a Blocker that refused to publish the
     * most common authoring shape in this product, and told an author staring at their embedded
     * video that the lesson had no content.
     */
    private const EMBED_ELEMENTS = [
        'iframe', 'img', 'video', 'audio', 'embed', 'object', 'source',
        // oembed is CKEditor 5's media output. Omitting it was a FALSE NEGATIVE that blocked
        // publishing a lesson whose only content was an embedded video — the exact failure this
        // whole rule was rewritten to stop. svg/canvas/picture/track cover the remaining shapes an
        // editor emits with no text alongside them.
        'oembed', 'svg', 'canvas', 'picture', 'track',
    ];

    /**
     * Characters that look like content but are not: every Unicode space plus the zero-width family.
     *
     * `<p>&nbsp;</p>` is what TinyMCE, CKEditor and Quill all emit for an empty paragraph — it is THE
     * canonical empty lesson. `trim()` strips only ASCII whitespace, so `\xC2\xA0` survived it and
     * the guard declared the lesson full.
     */
    private const BLANK_CHARACTERS = '\s\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200D}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}';

    /**
     * Payload keys that point AT something — a real attachment the learner will receive. A non-empty
     * value under one of these is substance even though the key carries no prose itself, which is
     * how a structured block like {"embed": {"media_id": 12}} is recognised: it has no string leaf
     * at all, and the old recursion (which only counted strings) called it empty.
     */
    private const REFERENCE_KEYS = [
        'media_id', 'mediaid', 'media_ids', 'mediaids',
        'asset_id', 'assetid', 'asset_ids', 'assetids',
        'file_id', 'fileid', 'file_ids', 'fileids',
        'image_id', 'imageid', 'image_ids', 'imageids',
        'video_id', 'videoid', 'video_ids', 'videoids',
        'attachment_id', 'attachmentid', 'attachment_ids', 'attachmentids',
        'url', 'src', 'href', 'embed_url', 'embedurl', 'source_url', 'sourceurl', 'path',
        // NOTE: `public_id` is deliberately NOT here. Every API-shaped payload echoes its own
        // public_id, so counting it would make any serialised block look substantial regardless of
        // what it holds.
    ];

    /**
     * Keys whose VALUE is a media object rather than a reference itself.
     *
     * Inside one of these, a bare `id` means a real attachment — `{"media": {"id": 7}}` is a lesson
     * with a video in it. Outside one, a bare `id` is just a serialised record's own identifier and
     * must not count, which is why this is scoped rather than added to REFERENCE_KEYS.
     */
    private const MEDIA_CONTAINER_KEYS = [
        'media', 'image', 'video', 'audio', 'file', 'attachment', 'asset', 'embed', 'poster', 'thumbnail',
    ];

    /**
     * Payload keys that only DESCRIBE the block — how it renders, not what it holds.
     *
     * Without this set the recursion counted every non-empty string leaf, so a stub payload of
     * {"type": "text"} — a block an author created and never filled in — read as substance and the
     * lesson passed. These keys are skipped rather than recursed into.
     */
    private const METADATA_KEYS = [
        'type', 'variant', 'kind', 'layout', 'align', 'alignment', 'position',
        'format', 'locale', 'lang', 'language', 'style', 'theme', 'version',
        'width', 'height', 'level', 'order', 'class', 'classname',
        // Presentation-only keys an editor emits alongside an EMPTY block. Without them a block
        // holding nothing but {"color": "red"} counted as substance and published a blank lesson.
        'color', 'colour', 'background', 'size', 'icon', 'placeholder', 'dir', 'label',
    ];

    /**
     * Whether a lesson/block payload carries anything a learner would actually receive.
     *
     * Three kinds of substance count, because all three are shapes an author legitimately ships:
     * readable text, an embedded media element, and a structured reference to attached media. Only
     * pure presentation metadata is treated as empty.
     */
    private function hasMeaningfulContent(mixed $value, bool $insideMediaContainer = false): bool
    {
        if (is_string($value)) {
            return $this->stringHasSubstance($value);
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            $normalized = is_string($key) ? strtolower(str_replace('-', '_', $key)) : null;

            // Presentation metadata never counts, whatever it holds.
            if ($normalized !== null && in_array($normalized, self::METADATA_KEYS, true)) {
                continue;
            }

            // A reference key with something real under it IS the content.
            if ($normalized !== null
                && in_array($normalized, self::REFERENCE_KEYS, true)
                && $this->isPresentReference($item)) {
                return true;
            }

            // A bare `id` counts only inside a media container: {"media": {"id": 7}} is a real
            // attachment, while a serialised record's own top-level id is not lesson content.
            if ($normalized === 'id' && $insideMediaContainer && $this->isPresentReference($item)) {
                return true;
            }

            $nested = $insideMediaContainer
                || ($normalized !== null && in_array($normalized, self::MEDIA_CONTAINER_KEYS, true));

            if ($this->hasMeaningfulContent($item, $nested)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Readable text once tags are stripped, OR an embedded media element.
     *
     * ORDER MATTERS: strip tags FIRST, decode entities SECOND.
     *
     * Decoding before stripping would turn `&lt;div&gt;` into real markup that strip_tags() then
     * removes — so a lesson teaching HTML, whose body is `<pre>&lt;div class="x"&gt;&lt;/div&gt;</pre>`,
     * would reduce to '' and be refused publication. That is precisely the "legitimate course
     * blocked" failure this rule exists to prevent, in an LMS where such lessons are ordinary
     * content. Stripping first leaves `&lt;div&gt;`, which decodes to the visible text `<div>` and
     * correctly counts as substance.
     *
     * Consequences, all deliberate:
     *   `<p>&nbsp;</p>`   -> "&nbsp;" -> NBSP -> blank        => EMPTY (the canonical empty lesson)
     *   `&#8203;`         -> zero-width space -> blank        => EMPTY
     *   `<pre>&lt;div&gt;</pre>` -> "<div>"                   => SUBSTANCE (a code sample)
     *   `&lt;img&gt;`     -> "<img>"                          => SUBSTANCE (visible characters)
     *   `&amp;`           -> "&"                              => SUBSTANCE (a visible character)
     *
     * The EMBED test runs on the RAW string, never the decoded one, so `&lt;img&gt;` can never be
     * mistaken for an actual embed — it is text about a tag, and it is counted as text.
     */
    private function stringHasSubstance(string $value): bool
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_replace('/['.self::BLANK_CHARACTERS.']+/u', '', $text) !== '') {
            return true;
        }

        // The trailing word boundary keeps <img> matching while <imgur> does not.
        return preg_match('/<\s*('.implode('|', self::EMBED_ELEMENTS).')\b/i', $value) === 1;
    }

    /**
     * A reference is present when it points at something real — 0 and '' do not.
     *
     * A LIST of references counts when any member does, because `{"media_ids": [12, 13]}` is a
     * lesson with two attachments. Without that, a multi-attachment block read as empty and blocked
     * publication of a perfectly good lesson.
     */
    private function isPresentReference(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return $value > 0;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->isPresentReference($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A quiz lesson pointing at a missing, draft or archived assessment renders as unavailable for
     * the learner — the publish-gated reference resolves to null. That is a broken lesson, and it
     * blocks: quiz lessons are new, so no existing published course can already be in this state.
     *
     * Resolved through LessonAssessmentPort rather than by querying assessments: Authoring is not
     * permitted to import an Assessment class, and an ArchitectureTest enforces it.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passed
     */
    private function checkQuizAssessments(Collection $lessons, array &$issues, array &$passed): void
    {
        $quizzes = $lessons->filter(
            fn (Lesson $l) => $l->type->usesAssessment() && $l->publish_state->isPublished(),
        );

        if ($quizzes->isEmpty()) {
            return;
        }

        $broken = false;

        // Resolved in ONE call for the whole course. describe() per lesson was a query plus a count
        // sub-select each, paid on every publish attempt AND every load of the instructor readiness
        // panel — a twenty-quiz course cost forty queries to answer one question.
        $refs = $this->assessments->describeMany(
            $quizzes->pluck('assessment_id')->filter()->map(fn ($id): int => (int) $id)->values()->all(),
        );

        foreach ($quizzes as $lesson) {
            // An id with no surviving assessment is absent from $refs, which reproduces describe()'s
            // null exactly: a stale reference degrades to "no quiz", never to a broken report.
            $ref = $lesson->assessment_id === null ? null : ($refs[(int) $lesson->assessment_id] ?? null);

            if ($ref !== null && $ref->isPublished()) {
                continue;
            }

            $broken = true;

            $issues[] = new ReadinessIssue(
                code: 'lesson.quiz_without_published_assessment',
                severity: ReadinessSeverity::Blocker,
                title: sprintf('The quiz lesson "%s" has no published quiz.', $lesson->title),
                explanation: $ref === null
                    ? 'No quiz is attached, so the lesson has nothing for a learner to take.'
                    : 'The attached quiz is still a draft, so learners see the lesson as unavailable.',
                recommendedAction: $ref === null
                    ? 'Attach a quiz to this lesson, or change the lesson type.'
                    : 'Publish the attached quiz.',
                entityType: 'lesson',
                entityPublicId: $lesson->public_id,
            );
        }

        if (! $broken) {
            $passed[] = 'lesson.quiz_without_published_assessment';
        }
    }
}
