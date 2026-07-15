<?php

namespace App\Domains\Catalog\Services;

use App\Contexts\Learning\Enums\EnrollmentStatus;
use App\Contexts\Learning\Models\Enrollment;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Contracts\UserLookupPort;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;

/**
 * Read-only analytics for the Instructor Portal. Concentrates the (baselined) cross-context
 * reads Catalog makes into Learning enrollments and the Shared curriculum port, so controllers
 * stay thin and the coupling surface is a single, auditable place.
 */
class InstructorAnalyticsService
{
    public function __construct(
        private readonly CurriculumReadPort $curriculum,
        private readonly UserLookupPort $users,
    ) {}

    /**
     * Per-course teaching stats.
     *
     * @return array{enrollments:int,completions:int,avg_progress:int,sections:int,lessons:int}
     */
    public function courseStats(Course $course): array
    {
        $agg = Enrollment::query()
            ->where('course_id', $course->id)
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as completions', [EnrollmentStatus::Completed->value])
            ->selectRaw('coalesce(round(avg(progress_percentage)), 0) as avg_progress')
            ->first();

        $tree = $this->curriculum->curriculumTree($course->id, false);
        $sections = count($tree['sections']);
        $lessons = array_sum(array_map(static fn (array $s): int => count($s['lessons']), $tree['sections']));

        return [
            'enrollments' => (int) ($agg->total ?? 0),
            'completions' => (int) ($agg->completions ?? 0),
            'avg_progress' => (int) ($agg->avg_progress ?? 0),
            'sections' => $sections,
            'lessons' => $lessons,
        ];
    }

    /**
     * Dashboard aggregate across all courses trained by the given user id.
     *
     * @return array{
     *   courses: array{total:int, draft:int, published:int, archived:int},
     *   students: int,
     *   completions: int,
     *   recent_enrollments: list<array{
     *     course:array{id:string,title:string}, student:array{id:?string,name:?string},
     *     status:string, progress_percentage:int, enrolled_at:?string
     *   }>
     * }
     */
    public function dashboard(int $userId): array
    {
        /** @var array<int, string> $courseTitles course_id => title */
        $courseTitles = Course::query()->forTrainer($userId)
            ->pluck('title', 'id')->all();
        $courseIds = array_keys($courseTitles);

        /** @var array<int, string> $publicIds course_id => public_id */
        $publicIds = Course::query()->forTrainer($userId)
            ->pluck('public_id', 'id')->all();

        $byStatus = Course::query()->forTrainer($userId)
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')->all();

        $students = $courseIds === [] ? 0 : (int) Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');

        $completions = $courseIds === [] ? 0 : (int) Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', EnrollmentStatus::Completed->value)
            ->count();

        $recent = $courseIds === [] ? collect() : Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->latest('enrolled_at')
            ->limit(10)
            ->get(['public_id', 'user_id', 'course_id', 'status', 'progress_percentage', 'enrolled_at']);

        $refs = $this->users->refsByIds($recent->pluck('user_id')->map(static fn ($v): int => (int) $v)->all());

        $recentRows = $recent->map(function (Enrollment $e) use ($courseTitles, $publicIds, $refs): array {
            $ref = $refs[(int) $e->user_id] ?? null;

            return [
                'course' => [
                    'id' => (string) ($publicIds[$e->course_id] ?? ''),
                    'title' => (string) ($courseTitles[$e->course_id] ?? ''),
                ],
                'student' => [
                    'id' => $ref?->publicId,
                    'name' => $ref?->name,
                ],
                'status' => $e->status->value,
                'progress_percentage' => (int) $e->progress_percentage,
                'enrolled_at' => $e->enrolled_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'courses' => [
                'total' => count($courseIds),
                'draft' => (int) ($byStatus[CourseStatus::Draft->value] ?? 0),
                'published' => (int) ($byStatus[CourseStatus::Published->value] ?? 0),
                'archived' => (int) ($byStatus[CourseStatus::Archived->value] ?? 0),
            ],
            'students' => $students,
            'completions' => $completions,
            'recent_enrollments' => $recentRows,
        ];
    }
}
