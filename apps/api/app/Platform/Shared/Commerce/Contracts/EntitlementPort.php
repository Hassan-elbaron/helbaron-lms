<?php

namespace App\Platform\Shared\Commerce\Contracts;

use App\Platform\Shared\Commerce\Data\CourseEntitlement;

/**
 * Cross-context entitlement boundary. This port lives in Shared so the Learning context can ask
 * "may this user access this course?" WITHOUT importing anything from Commerce — the whole point of
 * the boundary. Commerce owns the only implementation (EntitlementAdapter); consumers depend on
 * this contract, never on the concrete class.
 *
 * An entitlement is granted by EITHER a paid one-off purchase (an OrderCourseGrant on a paid order)
 * OR an active subscription whose plan's product bundles the course. Identifiers are internal
 * integer ids on both sides; no domain models cross the boundary — only scalars.
 */
interface EntitlementPort
{
    /**
     * Whether the given user is currently entitled to the given course, from any source.
     */
    public function hasCourseEntitlement(int $userId, int $courseId): bool;

    /**
     * The learner's entitlement to a course INCLUDING its kind and window, or null when they have none.
     *
     * hasCourseEntitlement() answers only "may they in?", which is enough to decide whether to refuse
     * the payment-free path but not enough to record the access correctly once it is allowed. Three
     * of the four entitlement sources are revocable or time-boxed — an individual subscription, an
     * organization seat-pool subscription, and a company-purchase seat — and the payment-free path
     * wrote every one of them as `source = free, expires_at = NULL`. Access is then decided by that
     * enrollment row alone (CourseEnrollmentAdapter never re-consults this port), so one POST to the
     * enrol endpoint converted a monthly subscription into permanent access to the whole bundled
     * catalogue, which no refund, lapse or seat revocation could reclaim.
     *
     * Callers must grant with the returned kind and window, never as a perpetual free grant.
     */
    public function courseEntitlement(int $userId, int $courseId): ?CourseEntitlement;

    /**
     * Every course id the user is currently entitled to, de-duplicated across all sources.
     *
     * @return list<int>
     */
    public function entitledCourseIds(int $userId): array;

    /**
     * Whether the course is sold commercially — i.e. an ACTIVE product grants it, directly or as
     * part of a bundle.
     *
     * Learning asks this before allowing payment-free self-enrolment: a course that is on sale must
     * not also be obtainable for nothing by calling the enrol endpoint. Kept on this port (rather
     * than Learning reading a Commerce model) so the boundary holds; only scalars cross.
     */
    public function isCoursePurchasable(int $courseId): bool;

    /**
     * Whether ANY product row grants the course, at any status — draft, active or archived.
     *
     * This, not isCoursePurchasable(), is the question the payment-free enrolment path must ask.
     * A draft or archived product still represents a course somebody intends to charge for; giving
     * it away because it is not active RIGHT NOW hands out a lifetime grant that no refund or
     * entitlement-revocation path undoes. Soft-deleted products are excluded: deleting a product is
     * a deliberate, permanent withdrawal from sale, after which the course is genuinely free again.
     */
    public function isCourseSold(int $courseId): bool;

    /**
     * Whether the payment-free enrolment path may open for this course.
     *
     * True only when the course is DECLARED free (an author's stated intent, owned by Catalog) AND
     * no active product currently sells it. Freeness is no longer inferred from the absence of a
     * product row: that inference made one `product_courses` row a one-way door, so an "All Access"
     * bundle including a free intro course, or a draft product created by mistake, un-freed the
     * course permanently with no admin escape hatch.
     */
    public function isCourseFreeToEnroll(int $courseId): bool;
}
