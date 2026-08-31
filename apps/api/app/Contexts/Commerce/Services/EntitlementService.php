<?php

namespace App\Contexts\Commerce\Services;

use App\Contexts\Commerce\Enums\BuyerType;
use App\Contexts\Commerce\Enums\CompanyEntitlementStatus;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Enums\SubscriptionStatus;
use App\Contexts\Commerce\Models\CompanyEntitlement;
use App\Contexts\Commerce\Models\CompanyEntitlementAssignment;
use App\Contexts\Commerce\Models\OrderCourseGrant;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Models\Subscription;
use App\Contexts\Commerce\Models\SubscriptionPlan;
use App\Contexts\Commerce\Support\SoldCourseIds;
use App\Platform\Shared\Catalog\Contracts\CourseLookupPort;
use App\Platform\Shared\Commerce\Data\CourseEntitlement;
use App\Platform\Shared\Commerce\Enums\EntitlementKind;
use App\Platform\Shared\Seats\Contracts\SeatProvisioningPort;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Read-side entitlement resolver. Answers "which courses may this user access, and why" by unioning
 * the four sources of access in Commerce:
 *
 *   (a) paid one-off purchases the user made FOR THEMSELVES — an OrderCourseGrant that hangs off an
 *       order whose status is Paid (a refunded/cancelled order is excluded because its status is no
 *       longer Paid). A company order never counts here: its buyer is an administrator, not a
 *       student, and its courses reach people only as seats (source (d));
 *   (b) active individual subscriptions — a Subscription the user owns (user_id) whose status
 *       grantsAccess() and whose current period has not yet ended, resolved through
 *       plan -> product -> courses to the bundled course ids; and
 *   (c) organization seat entitlements — a Subscription owned by an ORGANIZATION on whose seat pool
 *       the user currently holds an ACTIVE seat (user -> organization_members -> seat_assignments ->
 *       seat_pool_id -> subscription). Releasing the seat (or the subscription lapsing) revokes it,
 *       because both are re-evaluated on every read; and
 *   (d) company purchase seats — an active assignment on a CompanyEntitlement that a company bought
 *       outright and its manager handed to this employee, resolved through the purchased product to
 *       its courses. Like (c) it is recomputed on every read, so revoking the seat or the purchase
 *       lapsing takes the access away immediately.
 *
 * Sources (a)/(b)/(d) are Commerce models + scalars only. Source (c) reaches the CRM seat tables through
 * the Shared SeatProvisioningPort (the single Commerce->CRM seam), which returns only scalar pool ids
 * — so no Learning model and no CRM Eloquent class is imported here directly.
 *
 * TENANCY (T1, later): source (c) spans tenant-owned CRM tables; the pool-id lookup behind the
 * SeatProvisioningPort and the seat-pool subscription query below must be tenant-scoped when tenant
 * scoping lands.
 */
class EntitlementService extends BaseService
{
    public function __construct(
        private readonly SeatProvisioningPort $seats,
        private readonly CourseLookupPort $courses,
    ) {}

    /**
     * Every course id the user is currently entitled to, from all sources, de-duplicated and sorted
     * ascending for a stable response.
     *
     * @return list<int>
     */
    public function entitledCourseIds(int $userId): array
    {
        return $this->oneOffGrantCourseIds($userId)
            ->merge($this->subscriptionCourseIds($userId))
            ->merge($this->seatEntitledCourseIds($userId))
            ->merge($this->companyEntitlementCourseIds($userId))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Whether the user is entitled to a specific course from any source. Existence-only queries —
     * no rows are hydrated.
     */
    public function hasCourseEntitlement(int $userId, int $courseId): bool
    {
        return $this->hasOneOffGrant($userId, $courseId)
            || $this->hasActiveSubscriptionForCourse($userId, $courseId)
            || $this->hasSeatEntitlementForCourse($userId, $courseId)
            || $this->hasCompanyEntitlementForCourse($userId, $courseId);
    }

    /**
     * The learner's entitlement to a course WITH its kind and window, or null when they have none.
     *
     * Ordered strongest-first, because the answer decides how the resulting enrollment is recorded
     * and a learner may hold several at once. A one-off purchase outranks everything: it is the
     * learner's own and perpetual, so it must not be re-recorded as a seat that an employer's clock
     * can withdraw. Borrowed access reports the date it ends, so the enrollment carries a real
     * expiry instead of NULL.
     *
     * Windows are ISO-8601 strings — no Carbon and no Commerce model crosses the boundary.
     */
    public function courseEntitlement(int $userId, int $courseId): ?CourseEntitlement
    {
        if ($this->hasOneOffGrant($userId, $courseId)) {
            return new CourseEntitlement(EntitlementKind::Purchase);
        }

        $subscriptionEnd = $this->subscriptionAccessEnd($userId, $courseId);
        if ($subscriptionEnd !== null) {
            return new CourseEntitlement(EntitlementKind::Subscription, $subscriptionEnd);
        }

        $seatEnd = $this->seatAccessEnd($userId, $courseId);
        if ($seatEnd !== null) {
            return new CourseEntitlement(EntitlementKind::CompanySeat, $seatEnd['ends_at']);
        }

        return null;
    }

    /**
     * The latest period end among the learner's own access-granting subscriptions that bundle the
     * course, or null if none do. Latest wins: holding two overlapping subscriptions should give the
     * more generous window, not the first one the database happened to return.
     */
    private function subscriptionAccessEnd(int $userId, int $courseId): ?string
    {
        $end = null;

        foreach ($this->activeSubscriptions($userId)->with('plan.product.courses')->get() as $subscription) {
            if (! in_array($courseId, $this->courseIdsForSubscription($subscription), true)) {
                continue;
            }

            $periodEnd = $subscription->getAttribute('current_period_end');
            if ($periodEnd === null) {
                continue;
            }

            if ($end === null || $periodEnd->greaterThan($end)) {
                $end = $periodEnd;
            }
        }

        return $end?->toIso8601String();
    }

    /**
     * Employer-provided access to the course: an organization seat-pool subscription, or a company
     * purchase the manager assigned. Returns the latest end date across both, or null.
     *
     * A company purchase with no access_ends_at never expires on its own clock, but the seat can
     * still be revoked — which CompanySeatEnrollmentAdapter does by source, so recording it as
     * CompanySeat (rather than Free) is what makes revocation reach it at all.
     *
     * @return array{ends_at: string|null}|null
     */
    private function seatAccessEnd(int $userId, int $courseId): ?array
    {
        $end = null;
        $found = false;

        foreach ($this->activeSeatSubscriptions($userId) as $subscription) {
            if (! in_array($courseId, $this->courseIdsForSubscription($subscription), true)) {
                continue;
            }

            $found = true;
            $periodEnd = $subscription->getAttribute('current_period_end');
            if ($periodEnd !== null && ($end === null || $periodEnd->greaterThan($end))) {
                $end = $periodEnd;
            }
        }

        foreach ($this->liveCompanyEntitlements($userId) as $entitlement) {
            if (! in_array($courseId, $this->courseIdsForEntitlement($entitlement), true)) {
                continue;
            }

            $found = true;
            $accessEnd = $entitlement->getAttribute('access_ends_at');
            if ($accessEnd !== null && ($end === null || $accessEnd->greaterThan($end))) {
                $end = $accessEnd;
            }
        }

        return $found ? ['ends_at' => $end?->toIso8601String()] : null;
    }

    /**
     * Whether an ACTIVE product sells this course, on its own or inside a bundle.
     *
     * Draft and archived products are ignored: a course whose product is still being prepared is not
     * yet on sale, so it must not be locked away from the payment-free path in the meantime.
     */
    public function isCoursePurchasable(int $courseId): bool
    {
        return SoldCourseIds::activeOnly([$courseId]) !== [];
    }

    /**
     * Whether the payment-free enrolment path may open for this course.
     *
     * BOTH halves must agree. The course must be DECLARED free by its author (Catalog owns that
     * intent), and no active product may currently sell it. The second half is belt-and-braces: an
     * admin who ticks "free" on a course a live product sells has almost certainly made a mistake,
     * and checkout should win rather than the tick giving the course away.
     *
     * Note what is NOT here: a draft or archived product no longer blocks the free path. Freeness is
     * now stated rather than inferred, so an abandoned pricing experiment or an "All Access" bundle
     * that happens to include a free intro course cannot silently un-free it.
     */
    public function isCourseFreeToEnroll(int $courseId): bool
    {
        $declaredFree = $this->courses->freeFlagsForCourseIds([$courseId])[$courseId] ?? false;

        return $declaredFree && ! $this->isCoursePurchasable($courseId);
    }

    /**
     * Whether the course is sold at all — a product row of ANY status grants it, directly or in a
     * bundle.
     *
     * Deliberately status-blind, unlike isCoursePurchasable(). The payment-free enrolment path asks
     * this one, because a product parked in Draft (an admin editing its price) or moved to Archived
     * is still a course the business charges for. Answering with only ACTIVE products meant every
     * such window silently reopened a free lifetime grant on every course that product sells.
     *
     * Soft-deleted products do not count — the default Eloquent scope drops them here — so deleting
     * a product genuinely returns the course to the free path.
     */
    public function isCourseSold(int $courseId): bool
    {
        return SoldCourseIds::anyStatus([$courseId]) !== [];
    }

    /**
     * Course ids granted by paid one-off purchases: an OrderCourseGrant on an order the user owns
     * whose status is Paid.
     *
     * @return Collection<int, int>
     */
    private function oneOffGrantCourseIds(int $userId): Collection
    {
        return OrderCourseGrant::query()
            ->whereHas('order', fn ($query) => $this->personalPaidOrder($query, $userId))
            ->pluck('course_id')
            ->map(fn ($id): int => (int) $id);
    }

    private function hasOneOffGrant(int $userId, int $courseId): bool
    {
        return OrderCourseGrant::query()
            ->where('course_id', $courseId)
            ->whereHas('order', fn ($query) => $this->personalPaidOrder($query, $userId))
            ->exists();
    }

    /**
     * Constrain an order sub-query to a paid order the user bought FOR THEMSELVES. A company order is
     * excluded deliberately: the employee who ran the company's card is an administrator, not a
     * student, and its courses reach people only through the seats their manager assigns. Orders
     * predating buyer ownership carry no buyer_type and stay personal, which is what they were.
     *
     * @param  Builder<Model>  $query  the relation query whereHas() hands to its callback
     * @return Builder<Model>
     */
    private function personalPaidOrder(Builder $query, int $userId): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->whereIn('status', $this->paidOrderStatuses())
            ->where(fn ($q) => $q
                ->whereNull('buyer_type')
                ->orWhere('buyer_type', '!=', BuyerType::Company->value));
    }

    /**
     * Course ids the user may access as an EMPLOYEE holding a seat in a company purchase: an active
     * assignment on an entitlement that is still live, resolved to the bought product's courses.
     *
     * Read fresh on every request rather than trusted from the enrollment row, so a revoked seat or a
     * lapsed purchase stops granting access the moment it happens.
     *
     * @return Collection<int, int>
     */
    private function companyEntitlementCourseIds(int $userId): Collection
    {
        $ids = [];

        foreach ($this->liveCompanyEntitlements($userId) as $entitlement) {
            foreach ($this->courseIdsForEntitlement($entitlement) as $courseId) {
                $ids[$courseId] = $courseId;
            }
        }

        return new Collection(array_values($ids));
    }

    private function hasCompanyEntitlementForCourse(int $userId, int $courseId): bool
    {
        foreach ($this->liveCompanyEntitlements($userId) as $entitlement) {
            if (in_array($courseId, $this->courseIdsForEntitlement($entitlement), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The company purchases this user currently holds a seat in, filtered to those still granting:
     * status active, the access window open at both ends.
     *
     * @return Collection<int, CompanyEntitlement>
     */
    private function liveCompanyEntitlements(int $userId): Collection
    {
        $entitlementIds = CompanyEntitlementAssignment::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->pluck('company_entitlement_id');

        if ($entitlementIds->isEmpty()) {
            return new Collection;
        }

        return CompanyEntitlement::query()
            ->whereKey($entitlementIds)
            ->where('status', CompanyEntitlementStatus::Active->value)
            ->with('product.courses')
            ->get()
            ->filter(fn (CompanyEntitlement $entitlement): bool => $entitlement->isAssignable())
            ->values();
    }

    /**
     * @return list<int>
     */
    private function courseIdsForEntitlement(CompanyEntitlement $entitlement): array
    {
        $product = $entitlement->getRelationValue('product');

        return $product instanceof Product ? $product->courseIds() : [];
    }

    /**
     * Course ids bundled by the user's currently-active subscriptions, resolved through
     * plan -> product -> courses.
     *
     * @return Collection<int, int>
     */
    private function subscriptionCourseIds(int $userId): Collection
    {
        return $this->activeSubscriptions($userId)
            ->with('plan.product.courses')
            ->get()
            ->flatMap(function (Subscription $subscription): array {
                $courses = $subscription->plan?->product?->courses;

                if (! $courses instanceof Collection) {
                    return [];
                }

                return $courses
                    ->map(fn ($course): int => (int) $course->getKey())
                    ->all();
            });
    }

    private function hasActiveSubscriptionForCourse(int $userId, int $courseId): bool
    {
        return $this->activeSubscriptions($userId)
            ->whereHas('plan.product.courses', fn ($query) => $query->whereKey($courseId))
            ->exists();
    }

    /**
     * Base query for the user's access-granting subscriptions: a status that grantsAccess() and a
     * current period that has not yet lapsed.
     *
     * @return Builder<Subscription>
     */
    private function activeSubscriptions(int $userId): Builder
    {
        return Subscription::query()
            ->where('user_id', $userId)
            ->whereIn('status', $this->accessGrantingSubscriptionStatuses())
            ->where('current_period_end', '>', now());
    }

    /**
     * Course ids the user is entitled to as a SEATED EMPLOYEE: the organization subscriptions whose
     * seat pool the user currently holds an active seat on, that are access-granting and live now,
     * resolved through plan -> product -> courses.
     *
     * @return Collection<int, int>
     */
    private function seatEntitledCourseIds(int $userId): Collection
    {
        $ids = [];

        foreach ($this->activeSeatSubscriptions($userId) as $subscription) {
            foreach ($this->courseIdsForSubscription($subscription) as $courseId) {
                $ids[$courseId] = $courseId;
            }
        }

        return new Collection(array_values($ids));
    }

    private function hasSeatEntitlementForCourse(int $userId, int $courseId): bool
    {
        foreach ($this->activeSeatSubscriptions($userId) as $subscription) {
            if (in_array($courseId, $this->courseIdsForSubscription($subscription), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The organization subscriptions the user is seated on right now: bound to a seat pool the user
     * holds an active seat in, access-granting, and live per the wall clock (isActiveNow) — so a
     * canceled-but-not-elapsed org subscription still grants access while an expired one does not.
     *
     * @return Collection<int, Subscription>
     */
    private function activeSeatSubscriptions(int $userId): Collection
    {
        $poolIds = $this->seats->activeSeatPoolIdsForUser($userId);

        if ($poolIds === []) {
            return new Collection;
        }

        return Subscription::query()
            ->whereIn('seat_pool_id', $poolIds)
            ->whereIn('status', $this->accessGrantingSubscriptionStatuses())
            ->with('plan.product.courses')
            ->get()
            ->filter(fn (Subscription $subscription): bool => $subscription->isActiveNow())
            ->values();
    }

    /**
     * Course ids bundled by a subscription's plan -> product -> courses, resolved without importing a
     * Learning model (the product's courses relation is read generically).
     *
     * @return list<int>
     */
    private function courseIdsForSubscription(Subscription $subscription): array
    {
        $plan = $subscription->getRelationValue('plan');
        if (! $plan instanceof SubscriptionPlan) {
            return [];
        }

        $product = $plan->getRelationValue('product');
        if (! $product instanceof Model) {
            return [];
        }

        $courses = $product->getRelationValue('courses');
        if (! is_iterable($courses)) {
            return [];
        }

        $ids = [];
        foreach ($courses as $course) {
            if ($course instanceof Model) {
                $ids[] = (int) $course->getKey();
            }
        }

        return $ids;
    }

    /**
     * The order statuses that represent a settled, non-reversed purchase. Kept as a set so the
     * fulfilment vocabulary can grow without touching the callers.
     *
     * @return list<string>
     */
    private function paidOrderStatuses(): array
    {
        return [OrderStatus::Paid->value];
    }

    /**
     * The subscription status values whose enum grantsAccess() is true, projected to their backing
     * strings for a whereIn on the column.
     *
     * @return list<string>
     */
    private function accessGrantingSubscriptionStatuses(): array
    {
        return array_values(array_map(
            fn (SubscriptionStatus $status): string => $status->value,
            array_filter(
                SubscriptionStatus::cases(),
                fn (SubscriptionStatus $status): bool => $status->grantsAccess(),
            ),
        ));
    }
}
