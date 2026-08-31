<?php

namespace App\Contexts\Commerce\Adapters;

use App\Contexts\Commerce\Services\EntitlementService;
use App\Platform\Shared\Commerce\Contracts\EntitlementPort;
use App\Platform\Shared\Commerce\Data\CourseEntitlement;

/**
 * Commerce's implementation of the Shared EntitlementPort. Deliberately thin: it is the seam other
 * contexts (Learning) bind to, and it forwards to EntitlementService, which owns the actual
 * resolution over paid one-off grants and active subscriptions. Only Commerce models + scalars are
 * touched here — no Learning imports cross the boundary.
 */
class EntitlementAdapter implements EntitlementPort
{
    public function __construct(
        private readonly EntitlementService $entitlements,
    ) {}

    public function hasCourseEntitlement(int $userId, int $courseId): bool
    {
        return $this->entitlements->hasCourseEntitlement($userId, $courseId);
    }

    public function courseEntitlement(int $userId, int $courseId): ?CourseEntitlement
    {
        return $this->entitlements->courseEntitlement($userId, $courseId);
    }

    /**
     * @return list<int>
     */
    public function entitledCourseIds(int $userId): array
    {
        return $this->entitlements->entitledCourseIds($userId);
    }

    public function isCoursePurchasable(int $courseId): bool
    {
        return $this->entitlements->isCoursePurchasable($courseId);
    }

    public function isCourseSold(int $courseId): bool
    {
        return $this->entitlements->isCourseSold($courseId);
    }

    public function isCourseFreeToEnroll(int $courseId): bool
    {
        return $this->entitlements->isCourseFreeToEnroll($courseId);
    }
}
