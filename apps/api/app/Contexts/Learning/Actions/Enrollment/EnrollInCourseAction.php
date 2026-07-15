<?php

namespace App\Contexts\Learning\Actions\Enrollment;

use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Exceptions\CourseNotEnrollableException;
use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Actions\BaseAction;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;

/**
 * Self-service enrollment into a published course (payment-free). Delegates the actual grant
 * to GrantEnrollmentAction. Enrollability is resolved through CurriculumReadPort by course id.
 */
class EnrollInCourseAction extends BaseAction
{
    public function __construct(
        private readonly GrantEnrollmentAction $grant,
        private readonly CurriculumReadPort $curriculum,
    ) {}

    public function executeByUserId(int $userId, int $courseId): Enrollment
    {
        if (! $this->curriculum->isCourseEnrollable($courseId)) {
            throw new CourseNotEnrollableException;
        }

        return $this->grant->executeByUserId($userId, $courseId, EnrollmentSource::Free);
    }
}
