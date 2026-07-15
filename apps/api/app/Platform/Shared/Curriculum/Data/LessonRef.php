<?php

namespace App\Platform\Shared\Curriculum\Data;

/**
 * Immutable, read-only reference to a lesson, carrying only the fields Learning renders in the
 * curriculum tree / continue-learning views. No Eloquent, no media identifiers. `hasMedia` mirrors
 * the previous `relationLoaded('media') ? media !== null : null` semantics (null when not loaded).
 *
 * The structural fields (Phase 3A, additive, defaulted) — sectionId, courseId, position,
 * prerequisiteLessonIds — are populated by the id-based read methods (lessonRefById /
 * curriculumTree / orderedPublishedLessonRefs). The existing model mapper leaves them at defaults
 * (it maps only render fields and must avoid per-lesson queries in the tree loop).
 */
final readonly class LessonRef
{
    /**
     * @param  list<int>  $prerequisiteLessonIds
     * @param  array<string, mixed>|null  $content  lesson body (player detail); populated only by the
     *                                              detail read methods (findLessonByPublicId / lessonRefById), null elsewhere.
     */
    public function __construct(
        public int $id,
        public string $publicId,
        public string $title,
        public string $type,
        public bool $isPreview,
        public ?bool $hasMedia,
        public int $sectionId = 0,
        public int $courseId = 0,
        public int $position = 0,
        public array $prerequisiteLessonIds = [],
        public ?array $content = null,
    ) {}
}
