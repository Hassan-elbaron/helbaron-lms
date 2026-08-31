<?php

namespace App\Contexts\Learning\Enums;

/**
 * How an enrollment was granted. Commerce will use Purchase later; Learning stays payment-free.
 *
 * `CompanySeat` is the one source whose access is not the learner's own: it was handed out from an
 * organization's purchased seat pool and can be revoked or expire with that purchase. Telling it
 * apart from `Purchase` is what keeps a learner's personally bought access safe from their
 * employer's clock.
 */
enum EnrollmentSource: string
{
    case Free = 'free';
    case Purchase = 'purchase';
    case Manual = 'manual';
    case Grant = 'grant';
    case CompanySeat = 'company_seat';

    /**
     * Access granted by the learner's own active subscription, and ending with it.
     *
     * Added because there was no way to record it. A subscriber has no enrollment row until they
     * enrol, and the payment-free path wrote `Free` with `expires_at = NULL` — a permanent grant to
     * every course in the bundle that survived the subscription lapsing. Stored as a distinct source
     * so it is greppable, revocable and never mistaken for a purchase the learner owns.
     */
    case Subscription = 'subscription';

    /** Was this access handed out from an organization's purchase rather than earned by the learner? */
    public function isCompanySeat(): bool
    {
        return $this === self::CompanySeat;
    }

    /**
     * Is this access BORROWED — dependent on something that can lapse or be withdrawn — rather than
     * owned outright? Borrowed access must always carry an expiry.
     */
    public function isBorrowed(): bool
    {
        return $this === self::Subscription || $this === self::CompanySeat;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
