<?php

namespace App\Contexts\Commerce\Payments\Data;

/**
 * Provider-agnostic charge request. `reference` is our order public_id; the gateway returns
 * its own provider reference in ChargeResult. `idempotencyKey` (when set) lets gateways that
 * support it (e.g. Stripe's Idempotency-Key header) deduplicate retried charges; gateways
 * without idempotency support simply ignore it.
 */
final readonly class ChargeRequest
{
    public function __construct(
        public string $reference,
        public int $amountMinor,
        public string $currency,
        public string $description = '',
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}
}
