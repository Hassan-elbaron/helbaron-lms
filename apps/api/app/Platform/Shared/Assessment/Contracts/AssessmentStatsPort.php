<?php

namespace App\Platform\Shared\Assessment\Contracts;

use App\Platform\Shared\Assessment\Data\AssessmentPassRate;

/**
 * Aggregate quiz outcomes, for surfaces that report on courses without belonging to Assessment.
 *
 * Separate from LessonAssessmentPort on purpose. That port answers authoring questions — "may I
 * attach this?", "what is attached?" — and an ArchitectureTest pins it to exactly those two
 * methods. Reporting is a different concern with a different consumer, and folding it in would
 * quietly turn a narrow authoring contract into a general-purpose repository.
 *
 * Keyed by LESSON id rather than assessment id because that is the question a course-level report
 * actually asks: attempts are taken from a lesson, and the caller already knows its curriculum.
 * It also means the caller never has to learn an assessment id it has no other use for.
 */
interface AssessmentStatsPort
{
    /**
     * Graded-attempt totals across the given lessons.
     *
     * Only GRADED attempts count: an in-progress or awaiting-review sitting has no pass/fail
     * outcome yet, and counting it either way would misreport. An empty list returns an empty
     * result rather than querying.
     *
     * @param  list<int>  $lessonIds  internal lesson ids
     */
    public function passRateForLessons(array $lessonIds): AssessmentPassRate;
}
