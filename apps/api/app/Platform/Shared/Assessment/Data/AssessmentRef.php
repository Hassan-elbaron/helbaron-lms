<?php

namespace App\Platform\Shared\Assessment\Data;

/**
 * Immutable, read-only reference to an assessment — the ONLY assessment shape that crosses a
 * context boundary. No Eloquent model, no questions, no attempts, no grading, no publish machinery.
 *
 * Authoring uses this to render "this lesson has quiz X (12 questions, published)" in the
 * curriculum tree. Anything richer than that is an Assessment-domain concern and must be fetched
 * from the Assessment API directly, not smuggled through this DTO.
 */
final readonly class AssessmentRef
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $title,
        /** AssessmentStatus value as a scalar — the enum itself stays inside Assessment. */
        public string $status,
        public int $questionCount,
        public int $version,
    ) {}

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
