<?php

namespace App\Platform\Shared\Commerce\Data;

/**
 * What a buyer needs to know about acquiring a course, flattened for API output.
 *
 * Lives in Shared because Catalog renders it on the course endpoints while Commerce is the only
 * thing that can build it — the DTO is what crosses the boundary, never a Product model. Every
 * identifier here is a public id.
 *
 * `purchasable` and `free` are DELIBERATELY NOT complements. There are three states, not two:
 *
 *   purchasable=true,  free=false — an active product sells it; the buyer checks out.
 *   purchasable=false, free=true  — no product of ANY status grants it; payment-free enrolment is
 *                                   the supported path.
 *   purchasable=false, free=false — a product grants it but is not active (draft or archived), so
 *                                   it is not on sale YET and must not be given away either.
 *
 * The third state is the whole reason `free` exists. Freeness used to be inferred from
 * `purchasable === false`, which collapsed it into the second state: an admin moving a live product
 * to Draft for five minutes to edit its price turned every course that product sells into a
 * one-click free lifetime enrolment. Callers must branch on `free`, never on `! $purchasable`.
 */
final class PurchaseSummary
{
    /**
     * @param  list<string>  $includedInBundleIds  public ids of bundles that also grant the course
     */
    public function __construct(
        public readonly bool $purchasable,
        public readonly bool $free = false,
        public readonly ?string $productId = null,
        public readonly ?string $productType = null,
        public readonly ?string $currency = null,
        public readonly ?int $amountMinor = null,
        public readonly ?int $effectiveMinor = null,
        public readonly bool $onSale = false,
        public readonly ?string $audience = null,
        public readonly ?string $accessDurationType = null,
        public readonly ?int $accessDurationValue = null,
        public readonly ?string $accessEndsAt = null,
        public readonly bool $certificateEnabled = false,
        public readonly ?string $certificateExpiryType = null,
        public readonly ?int $certificateExpiryValue = null,
        public readonly array $includedInBundleIds = [],
    ) {}

    /**
     * A course nothing sells at any status — the only shape that authorises payment-free enrolment.
     */
    public static function free(): self
    {
        return new self(purchasable: false, free: true);
    }

    /**
     * A course a product grants but does not currently sell (the product is draft or archived).
     * Not buyable and not free: the surface should say "not available yet".
     */
    public static function notAvailable(): self
    {
        return new self(purchasable: false, free: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (! $this->purchasable) {
            return ['purchasable' => false, 'free' => $this->free];
        }

        return [
            'purchasable' => true,
            // Always present so a client can branch on one key without first checking purchasable.
            'free' => false,
            'product_id' => $this->productId,
            'product_type' => $this->productType,
            'price' => [
                'currency' => $this->currency,
                'amount_minor' => $this->amountMinor,
                'effective_minor' => $this->effectiveMinor,
                'on_sale' => $this->onSale,
            ],
            'audience' => $this->audience,
            'access' => [
                'duration_type' => $this->accessDurationType,
                'duration_value' => $this->accessDurationValue,
                'ends_at' => $this->accessEndsAt,
            ],
            'certificate' => [
                'enabled' => $this->certificateEnabled,
                'expiry_type' => $this->certificateExpiryType,
                'expiry_value' => $this->certificateExpiryValue,
            ],
            'included_in_bundles' => $this->includedInBundleIds,
        ];
    }
}
