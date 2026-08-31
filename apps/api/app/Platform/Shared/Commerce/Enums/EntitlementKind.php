<?php

namespace App\Platform\Shared\Commerce\Enums;

/**
 * WHERE a learner's access to a course came from — and, by implication, whether it can be taken away.
 *
 * The distinction is the whole point. A one-off purchase is the learner's own and is perpetual; a
 * subscription seat and an employer seat are BORROWED and end when the subscription lapses or the
 * seat is withdrawn. Collapsing them — which is what recording every entitlement as a payment-free
 * `Free` enrollment did — converts revocable, time-boxed access into a permanent grant that no
 * refund, lapse or revocation path can reclaim.
 */
enum EntitlementKind: string
{
    /** The learner bought this course outright. Theirs to keep. */
    case Purchase = 'purchase';

    /** Granted by the learner's own active subscription. Ends with the billing period. */
    case Subscription = 'subscription';

    /** Handed out from an organization's seat pool or company purchase. Ends with the seat. */
    case CompanySeat = 'company_seat';
}
