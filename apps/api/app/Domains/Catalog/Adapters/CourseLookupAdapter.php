<?php

namespace App\Domains\Catalog\Adapters;

use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Models\CourseTrainer;
use App\Platform\Shared\Catalog\Contracts\CourseLookupPort;

final class CourseLookupAdapter implements CourseLookupPort
{
    public function publishedCourseByPublicId(string $publicId): ?array
    {
        $course = Course::query()
            ->published()
            ->where('public_id', $publicId)
            ->first(['id', 'public_id', 'title']);

        if ($course === null) {
            return null;
        }

        return [
            'id' => (int) $course->getKey(),
            'public_id' => (string) $course->getAttribute('public_id'),
            'title' => (string) $course->getAttribute('title'),
        ];
    }

    /**
     * @return list<int>
     */
    public function trainerUserIds(int $courseId): array
    {
        return CourseTrainer::query()
            ->where('course_id', $courseId)
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $courseIds
     * @return array<int, bool>
     */
    public function freeFlagsForCourseIds(array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        if ($courseIds === []) {
            return [];
        }

        // withoutGlobalScopes(): the answer must not depend on the request's resolved tenant. A
        // guard whose failure mode is "the course looks free" is the wrong shape — see the matching
        // note on EntitlementService::isCourseSold().
        $flags = Course::withoutGlobalScopes()
            ->whereIn('id', $courseIds)
            ->pluck('is_free', 'id');

        $result = [];
        foreach ($courseIds as $id) {
            $result[$id] = (bool) ($flags[$id] ?? false);
        }

        return $result;
    }

    public function courseTitle(int $courseId): ?string
    {
        $title = Course::query()->whereKey($courseId)->value('title');

        return $title === null ? null : (string) $title;
    }
}
