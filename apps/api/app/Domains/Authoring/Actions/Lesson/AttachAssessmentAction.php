<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use Illuminate\Validation\ValidationException;

/**
 * Points a lesson at an assessment, or clears the reference.
 *
 * Authoring owns `lessons.assessment_id`, so Authoring performs the write — but it does not know
 * what a valid assessment is. LessonAssessmentPort answers that, scoped to the lesson's own course,
 * so an instructor cannot attach an assessment belonging to a course they do not train. The port
 * returns null for both "no such assessment" and "not yours", which is why this raises a single
 * indistinguishable error rather than leaking which case occurred.
 */
class AttachAssessmentAction
{
    public function __construct(private readonly LessonAssessmentPort $assessments) {}

    /** @param  string|null  $assessmentPublicId  null detaches */
    public function execute(Lesson $lesson, ?string $assessmentPublicId): Lesson
    {
        if ($assessmentPublicId === null) {
            $lesson->forceFill(['assessment_id' => null])->save();

            return $lesson->refresh();
        }

        // Narrowed explicitly: Lesson::section() is a bare BelongsTo, so static analysis sees only
        // Model here. Same pattern LessonPolicy already uses to reach the parent course.
        //
        // The course id is read off Section's own FK rather than by loading the Course model —
        // Authoring may not depend on Catalog, and the scalar is all the port needs.
        $section = $lesson->section;

        if (! $section instanceof Section) {
            throw ValidationException::withMessages([
                'assessment_id' => 'This lesson is not attached to a course.',
            ]);
        }

        $ref = $this->assessments->resolveAttachable($assessmentPublicId, $section->course_id);

        if ($ref === null) {
            throw ValidationException::withMessages([
                'assessment_id' => 'That assessment is not available for this course.',
            ]);
        }

        $lesson->forceFill(['assessment_id' => $ref->id])->save();

        return $lesson->refresh();
    }
}
