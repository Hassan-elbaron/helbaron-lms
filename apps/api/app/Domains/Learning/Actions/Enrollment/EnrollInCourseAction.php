<?php

namespace App\Domains\Learning\Actions\Enrollment;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Domains\Identity\Models\User;
use App\Domains\Learning\Enums\EnrollmentSource;
use App\Domains\Learning\Exceptions\CourseNotEnrollableException;
use App\Domains\Learning\Models\Enrollment;
use App\Shared\Actions\BaseAction;

/**
 * Self-service enrollment into a published course (payment-free). Delegates the actual grant
 * to GrantEnrollmentAction to keep entitlement logic in one place.
 */
class EnrollInCourseAction extends BaseAction
{
    public function __construct(private readonly GrantEnrollmentAction $grant) {}

    public function execute(User $user, Course $course): Enrollment
    {
        if ($course->status !== CourseStatus::Published) {
            throw new CourseNotEnrollableException;
        }

        return $this->grant->execute($user, $course, EnrollmentSource::Free);
    }
}
