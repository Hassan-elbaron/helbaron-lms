<?php

namespace App\Platform\Shared\Publishing\Data;

use App\Platform\Shared\Publishing\Enums\ReadinessSeverity;

/**
 * One finding from a publish-readiness evaluation.
 *
 * Scalars only, and `entityPublicId` is a PUBLIC id: this DTO crosses out of the domain and into
 * an HTTP resource, so it must never carry an internal key or a model.
 *
 * `code` is the stable machine identifier — the frontend keys translations and deep links off it,
 * never off `title`, which is prose and may be reworded.
 */
final readonly class ReadinessIssue
{
    /**
     * @param  string  $code  stable identifier, e.g. `course.no_sections`
     * @param  string|null  $entityType  `course` | `section` | `lesson` — what to link the author to
     * @param  string|null  $entityPublicId  public id of that entity, never an internal one
     */
    public function __construct(
        public string $code,
        public ReadinessSeverity $severity,
        public string $title,
        public string $explanation,
        public string $recommendedAction,
        public ?string $entityType = null,
        public ?string $entityPublicId = null,
    ) {}

    public function blocksPublishing(): bool
    {
        return $this->severity->blocksPublishing();
    }
}
