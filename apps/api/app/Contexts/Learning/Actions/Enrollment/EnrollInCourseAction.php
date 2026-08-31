<?php

namespace App\Contexts\Learning\Actions\Enrollment;

use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Exceptions\CourseNotEnrollableException;
use App\Contexts\Learning\Exceptions\CoursePurchaseRequiredException;
use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Actions\BaseAction;
use App\Platform\Shared\Commerce\Contracts\EntitlementPort;
use App\Platform\Shared\Commerce\Data\CourseEntitlement;
use App\Platform\Shared\Commerce\Enums\EntitlementKind;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use Carbon\CarbonImmutable;

/**
 * Self-service enrolment into a published course. Delegates the actual grant to
 * GrantEnrollmentAction; enrollability is resolved through CurriculumReadPort by course id.
 *
 * This is the payment-free path, so it must never hand out a course that is on sale. A course sold
 * by a product of ANY status is refused here unless the caller already holds an entitlement for it —
 * the paid and company/manager paths go straight to GrantEnrollmentAction and are unaffected. Both
 * checks read Shared ports, so Learning still imports nothing from Commerce.
 *
 * FREENESS IS STATED, NOT INFERRED. The free branch opens only when the course is DECLARED free by
 * its author and no active product sells it (isCourseFreeToEnroll). It used to be inferred from the
 * absence of an ACTIVE product, so a course whose product sat in Draft or Archived fell through to
 * the free branch — an admin moving a live product to Draft for a few minutes to edit its pricing
 * opened a free front door on every course that product sells. Inferring from the absence of ANY
 * product row closed that, but made a single product_courses row a one-way door with no admin
 * escape hatch; an explicit flag is neither.
 *
 * THE ENTITLEMENT PATH RECORDS WHAT IT ACTUALLY GRANTED. An entitled learner is let through — a
 * buyer whose order is already fulfilled must still be able to use this endpoint — but the resulting
 * enrollment now carries the entitlement's OWN source and window instead of being written as a
 * payment-free perpetual grant. Three of the four entitlement sources are revocable or time-boxed,
 * and access is decided by the enrollment row alone (CourseEnrollmentAdapter never re-consults the
 * entitlement port). Recording a monthly subscriber as `source = free, expires_at = NULL` therefore
 * converted one POST per course into permanent access to the entire bundled catalogue, which no
 * refund, lapse or seat revocation could reclaim.
 */
class EnrollInCourseAction extends BaseAction
{
    public function __construct(
        private readonly GrantEnrollmentAction $grant,
        private readonly CurriculumReadPort $curriculum,
        private readonly EntitlementPort $entitlements,
    ) {}

    public function executeByUserId(int $userId, int $courseId): Enrollment
    {
        if (! $this->curriculum->isCourseEnrollable($courseId)) {
            throw new CourseNotEnrollableException;
        }

        if ($this->entitlements->isCourseFreeToEnroll($courseId)) {
            // Declared free by its author AND not sold by an active product.
            return $this->grant->executeByUserId($userId, $courseId, EnrollmentSource::Free);
        }

        $entitlement = $this->entitlements->courseEntitlement($userId, $courseId);

        if ($entitlement === null) {
            throw new CoursePurchaseRequiredException;
        }

        return $this->grant->executeByUserId(
            $userId,
            $courseId,
            $this->sourceFor($entitlement),
            $this->expiryFor($entitlement),
        );
    }

    /**
     * Map the entitlement's kind onto the enrollment vocabulary.
     *
     * CompanySeat matters beyond bookkeeping: CompanySeatEnrollmentAdapter::revokeCompanySeat()
     * filters on `source = company_seat`, so an employer-provided seat recorded under any other
     * source is unreachable by revocation.
     */
    private function sourceFor(CourseEntitlement $entitlement): EnrollmentSource
    {
        return match ($entitlement->kind) {
            EntitlementKind::Purchase => EnrollmentSource::Purchase,
            EntitlementKind::Subscription => EnrollmentSource::Subscription,
            EntitlementKind::CompanySeat => EnrollmentSource::CompanySeat,
        };
    }

    /**
     * The window the enrollment must carry, or null for access that genuinely does not expire.
     *
     * A one-off purchase is perpetual. Borrowed access reports the date its subscription period or
     * seat window closes; a company purchase with no end date reports null, because that access ends
     * by seat revocation rather than by a clock.
     */
    private function expiryFor(CourseEntitlement $entitlement): ?CarbonImmutable
    {
        return $entitlement->expiresAt === null
            ? null
            : CarbonImmutable::parse($entitlement->expiresAt);
    }
}
