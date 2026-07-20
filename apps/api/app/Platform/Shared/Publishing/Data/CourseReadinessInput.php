<?php

namespace App\Platform\Shared\Publishing\Data;

/**
 * The course-level facts a readiness evaluation needs, flattened to scalars.
 *
 * This exists so the evaluator can live in Authoring without importing Catalog's Course model —
 * Authoring is not permitted to depend on Catalog, and the existing references are grandfathered
 * rather than allowed. Catalog owns Course and does the mapping; Authoring receives only what it
 * needs to answer the question.
 *
 * A side benefit: the rules become trivially testable without touching a database.
 */
final readonly class CourseReadinessInput
{
    public function __construct(
        public int $courseId,
        public string $coursePublicId,
        public ?string $description,
        public ?string $thumbnailPath,
        public bool $hasInstructor,
    ) {}
}
