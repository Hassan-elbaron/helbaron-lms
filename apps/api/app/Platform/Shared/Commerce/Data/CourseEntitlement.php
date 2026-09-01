<?php

namespace App\Platform\Shared\Commerce\Data;

use App\Platform\Shared\Commerce\Enums\EntitlementKind;

/**
 * A resolved answer to "may this learner access this course, and on what terms?".
 *
 * Replaces a bare boolean. The boolean was enough to decide whether to REFUSE the payment-free
 * enrolment path, but not enough to record the access correctly once it was allowed: every caller
 * fell through to a `Free` enrollment with `expires_at = NULL`, so a monthly subscriber who pressed
 * "Enroll" received permanent access to the course and nothing could reclaim it.
 *
 * Carries the window as an ISO-8601 string so neither a Carbon instance nor a Commerce model crosses
 * the boundary — Learning receives scalars and a Shared enum, nothing more.
 */
final readonly class CourseEntitlement
{
    public function __construct(
        public EntitlementKind $kind,
        /** When the access ends, or null for access that does not expire (a one-off purchase). */
        public ?string $expiresAt = null,
    ) {}

    /** Access that ends on a date — everything borrowed rather than bought. */
    public function isTimeBoxed(): bool
    {
        return $this->expiresAt !== null;
    }
}
