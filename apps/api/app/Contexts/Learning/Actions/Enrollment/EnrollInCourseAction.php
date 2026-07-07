<?php

namespace App\Contexts\Learning\Actions\Enrollment;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Exceptions\CourseNotEnrollableException;
use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Actions\BaseAction;

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
