<?php

namespace App\Domains\Authoring\Services;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Shared\Services\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Validates a course's curriculum and prerequisite graph. Pure read logic — returns error
 * strings; callers decide how to surface them.
 */
class CurriculumValidator extends BaseService
{
    /**
     * @return array<int, string> list of human-readable errors (empty = valid)
     */
    public function validateForPublish(Course $course): array
    {
        $errors = [];

        $sectionIds = Section::where('course_id', $course->id)->pluck('id');

        if ($sectionIds->isEmpty()) {
            $errors[] = 'The course has no sections.';

            return $errors;
        }

        $publishedLessons = Lesson::whereIn('section_id', $sectionIds)->published()->count();

        if (config('authoring.publish.require_published_lesson', true) && $publishedLessons === 0) {
            $errors[] = 'The course has no published lessons.';
        }

        return $errors;
    }

    /** Are all prerequisite ids within the same course as the lesson? */
    public function assertSameCourse(Lesson $lesson, array $prerequisiteIds): bool
    {
        if ($prerequisiteIds === []) {
            return true;
        }

        $courseId = Section::whereKey($lesson->section_id)->value('course_id');

        $foreign = Lesson::whereIn('lessons.id', $prerequisiteIds)
            ->join('course_sections', 'lessons.section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', '!=', $courseId)
            ->exists();

        return ! $foreign;
    }

    /**
     * Would adding these prerequisites to $lessonId create a cycle? Builds the current
     * dependency graph plus the proposed edges and DFS-detects a back-edge.
     *
     * @param  array<int, int>  $proposedPrerequisiteIds
     */
    public function wouldCreateCycle(int $lessonId, array $proposedPrerequisiteIds): bool
    {
        if (in_array($lessonId, $proposedPrerequisiteIds, true)) {
            return true;
        }

        // adjacency: lesson => its prerequisites (edges point to things that must come first)
        $edges = [];
        foreach (DB::table('lesson_prerequisites')->get() as $row) {
            $edges[$row->lesson_id][] = $row->prerequisite_lesson_id;
        }
        $edges[$lessonId] = array_values(array_unique(array_merge($edges[$lessonId] ?? [], $proposedPrerequisiteIds)));

        $visited = [];
        $stack = [];

        $dfs = function (int $node) use (&$dfs, &$edges, &$visited, &$stack): bool {
            $visited[$node] = true;
            $stack[$node] = true;

            foreach ($edges[$node] ?? [] as $next) {
                if (! ($visited[$next] ?? false)) {
                    if ($dfs($next)) {
                        return true;
                    }
                } elseif ($stack[$next] ?? false) {
                    return true;
                }
            }

            $stack[$node] = false;

            return false;
        };

        return $dfs($lessonId);
    }
}
