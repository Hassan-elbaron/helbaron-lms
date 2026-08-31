<?php

namespace App\Contexts\Commerce\Support;

use App\Platform\Shared\Branding\Contracts\BrandProfilePort;

/**
 * Builds the description a payment carries to the gateway — the text a customer sees on their bank
 * or card statement.
 *
 * WHY THIS EXISTS. Six call sites built `'HElbaron order '.$id` by hand. On a product sold as a
 * separate deployment per academy, that means a learner who bought from one academy sees a different
 * company's name on their statement — the single most alarming place a branding leak can surface,
 * and a plausible trigger for a chargeback.
 *
 * TRUNCATION IS DELIBERATE, AND IT TRUNCATES THE BRAND. Gateway descriptor fields are length-limited
 * (Stripe's statement descriptor is 22 characters; others differ), and an academy with a long legal
 * name would otherwise silently overflow or have the charge rejected. The REFERENCE is never
 * shortened: it is what reconciles the payment to the order, and a truncated reference turns a
 * support question into an investigation. When the whole string will not fit, the brand gives way —
 * a customer can recognise an unfamiliar-but-short brand plus their own order id far more easily
 * than a recognisable brand plus a mangled one.
 */
final class PaymentDescriptor
{
    /** A conservative ceiling that fits the common gateway limits; overridable per deployment. */
    private const DEFAULT_MAX_LENGTH = 100;

    /** The shortest brand fragment worth showing; below this the brand is dropped entirely. */
    private const MIN_BRAND_LENGTH = 3;

    public static function forOrder(string $reference): string
    {
        return self::build('order', $reference);
    }

    public static function forSubscription(string $reference): string
    {
        return self::build('subscription', $reference);
    }

    public static function forSubscriptionRenewal(string $reference): string
    {
        return self::build('subscription renewal', $reference);
    }

    public static function forSubscriptionUpgrade(string $reference): string
    {
        return self::build('subscription upgrade', $reference);
    }

    public static function forOrganizationSubscription(string $reference): string
    {
        return self::build('organization subscription', $reference);
    }

    /**
     * "{brand} {kind} {reference}", trimmed to the configured maximum by shortening the BRAND only.
     */
    public static function build(string $kind, string $reference): string
    {
        $reference = trim($reference);
        $kind = trim($kind);
        $max = self::maxLength();

        $tail = trim($kind.' '.$reference);
        $brand = self::brand();

        if ($brand === '') {
            return mb_substr($tail, 0, $max);
        }

        $full = $brand.' '.$tail;

        if (mb_strlen($full) <= $max) {
            return $full;
        }

        // Give the brand whatever is left once the reference and kind are accounted for.
        $room = $max - mb_strlen($tail) - 1;

        if ($room < self::MIN_BRAND_LENGTH) {
            return mb_substr($tail, 0, $max);
        }

        return mb_substr($brand, 0, $room).' '.$tail;
    }

    private static function brand(): string
    {
        // The port degrades to defaults and never throws, so a branding problem cannot stop a
        // payment being taken.
        return trim(app(BrandProfilePort::class)->profile()->name);
    }

    private static function maxLength(): int
    {
        $configured = config('commerce.payment.descriptor_max_length', self::DEFAULT_MAX_LENGTH);

        return is_numeric($configured) && (int) $configured > 0
            ? (int) $configured
            : self::DEFAULT_MAX_LENGTH;
    }
}
