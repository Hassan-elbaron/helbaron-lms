<?php

namespace App\Platform\Shared\Assessment\Contracts;

use App\Platform\Shared\Assessment\Data\AssessmentRef;

/**
 * The complete surface Authoring is allowed to know about assessments. Owned and implemented by the
 * Assessment domain; consumed by Authoring so a lesson can reference an assessment without
 * Authoring importing a single Assessment class.
 *
 * Deliberately NOT a repository. There is no list(), no find(), no save(). It answers exactly the
 * two questions Authoring has — "may I attach this?" and "what is attached?" — and nothing else.
 * Grading, attempts, questions, options, scoring and publishing never appear here; if Authoring
 * ever appears to need one of those, that is a signal the feature belongs in Assessment, not a
 * signal to widen this interface.
 *
 * Writing `lessons.assessment_id` is NOT on this port: that column belongs to Authoring, so
 * Authoring performs the write in its own action after this port has validated the reference.
 * Putting the write here would invert ownership of an Authoring table.
 */
interface LessonAssessmentPort
{
    /**
     * Resolve an assessment that may legitimately be attached to a lesson in `$courseId`.
     *
     * Returns null — never throws — when the assessment does not exist, is soft-deleted, is
     * archived, or belongs to a different course. Returning null for "wrong course" rather than a
     * distinguishable error is deliberate: it denies an instructor the ability to probe for the
     * existence of assessments in courses they do not train.
     *
     * @param  string  $assessmentPublicId  public_id, never an internal autoincrement id
     * @param  int  $courseId  the course the target lesson belongs to
     */
    public function resolveAttachable(string $assessmentPublicId, int $courseId): ?AssessmentRef;

    /**
     * Describe an already-attached assessment for display. Returns null when the id is unknown or
     * the assessment has since been deleted, so a stale reference degrades to "no quiz" instead of
     * breaking the curriculum tree.
     */
    public function describe(int $assessmentId): ?AssessmentRef;
}
