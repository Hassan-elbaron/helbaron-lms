<?php

namespace App\Platform\Shared\Curriculum\Data;

/**
 * Immutable, read-only reference to a course, carrying only the fields Learning renders. No
 * Eloquent, no status/pricing logic. Produced by the CurriculumReadPort from a loaded model.
 *
 * `thumbnailPath` (Phase 3A, additive, defaulted) is populated by the id-based read methods
 * (findCourseByPublicId / courseRefById); the existing model mapper leaves it null.
 */
final readonly class CourseRef
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $title,
        public string $slug,
        public ?string $thumbnailPath = null,
    ) {}
}
