<?php

namespace App\Domains\Authoring\Services;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Shared\Services\BaseService;
use App\Shared\ValueObjects\Duration;

/**
 * Estimates total course duration by summing lesson media durations (metadata only — this is
 * NOT progress tracking).
 */
class CompletionEstimationService extends BaseService
{
    public function estimate(Course $course, bool $publishedOnly = true): Duration
    {
        $sectionIds = Section::where('course_id', $course->id)
            ->when($publishedOnly, fn ($q) => $q->published())
            ->pluck('id');

        $seconds = (int) Lesson::whereIn('section_id', $sectionIds)
            ->when($publishedOnly, fn ($q) => $q->published())
            ->join('lesson_media', 'lessons.id', '=', 'lesson_media.lesson_id')
            ->sum('lesson_media.duration');

        return Duration::fromSeconds($seconds);
    }
}
