<?php

namespace App\Domains\Authoring\Services;

use App\Domains\Catalog\Contracts\CoursePublishGuard;
use App\Domains\Catalog\Models\Course;

/**
 * Authoring's implementation of Catalog's CoursePublishGuard. Bound in AuthoringServiceProvider
 * so publishing a course is blocked unless its curriculum is valid. This inverts the
 * dependency (Authoring depends on Catalog, never the reverse).
 */
class CurriculumPublishGuard implements CoursePublishGuard
{
    private ?string $reason = null;

    public function __construct(private readonly CurriculumValidator $validator) {}

    public function canPublish(Course $course): bool
    {
        $errors = $this->validator->validateForPublish($course);
        $this->reason = $errors[0] ?? null;

        return $errors === [];
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
