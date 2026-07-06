<?php

namespace App\Domains\Commerce\Payments\Data;

/**
 * Provider-agnostic charge request. `reference` is our order public_id; the gateway returns
 * its own provider reference in ChargeResult.
 */
final readonly class ChargeRequest
{
    public function __construct(
        public string $reference,
        public int $amountMinor,
        public string $currency,
        public string $description = '',
        public array $metadata = [],
    ) {}
}
