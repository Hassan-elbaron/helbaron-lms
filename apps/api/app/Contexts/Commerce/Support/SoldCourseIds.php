<?php

namespace App\Contexts\Commerce\Support;

use App\Contexts\Commerce\Enums\ProductStatus;
use Illuminate\Support\Facades\DB;

/**
 * THE single query behind "which of these courses does a product grant?".
 *
 * There were two, and they disagreed. PurchaseSummaryAdapter joined the `product_courses` pivot
 * directly, while EntitlementService used `whereHas('courses', …)` — which applies every global
 * scope declared on the Course model, including SoftDeletes and SharedOrOwnedTenantScope. So the
 * catalogue's answer was tenant-independent and the GUARD's answer was not: the guard's verdict
 * changed with the request's resolved tenant.
 *
 * Where they disagreed, the guard returned false and the payment-free enrolment path opened on a
 * course somebody is selling. A security guard whose failure mode is "give it away" is the wrong
 * shape, so this asks the pivot table directly and deliberately applies NO model scopes:
 *
 *   - Tenancy must not decide it. Whether a course is sold is a fact about product rows, not about
 *     who is asking. (EntitlementService already carried a `TENANCY (T1, later)` note here.)
 *   - Soft-deleted PRODUCTS are excluded, because withdrawing a product from sale is a deliberate
 *     act that should return the course to its declared state. Soft-deleted COURSES are irrelevant:
 *     nobody can enrol in one, so hiding the fact that it is sold would only weaken the guard.
 */
final class SoldCourseIds
{
    /**
     * Course ids granted by a product of ANY status — draft, active or archived.
     *
     * This is the question the payment-free enrolment guard asks. A draft or archived product still
     * represents a course somebody intends to charge for.
     *
     * @param  list<int>  $courseIds
     * @return array<int, true> keyed by course id, for O(1) membership tests
     */
    public static function anyStatus(array $courseIds): array
    {
        return self::query($courseIds, null);
    }

    /**
     * Course ids granted by an ACTIVE product — "on sale right now".
     *
     * This is the question pricing and the checkout path ask. It is deliberately a different
     * question from anyStatus(): the two disagree exactly while a product sits in Draft or Archived,
     * and that gap is where the course is neither buyable nor free.
     *
     * @param  list<int>  $courseIds
     * @return array<int, true>
     */
    public static function activeOnly(array $courseIds): array
    {
        return self::query($courseIds, ProductStatus::Active->value);
    }

    /**
     * @param  list<int>  $courseIds
     * @return array<int, true>
     */
    private static function query(array $courseIds, ?string $status): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        if ($courseIds === []) {
            return [];
        }

        $query = DB::table('product_courses')
            ->join('products', 'products.id', '=', 'product_courses.product_id')
            ->whereIn('product_courses.course_id', $courseIds)
            // Soft-deleted products no longer sell anything.
            ->whereNull('products.deleted_at');

        if ($status !== null) {
            $query->where('products.status', $status);
        }

        $ids = $query->distinct()->pluck('product_courses.course_id');

        $found = [];
        foreach ($ids as $id) {
            $found[(int) $id] = true;
        }

        return $found;
    }
}
