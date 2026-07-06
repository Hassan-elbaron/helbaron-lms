<?php

namespace App\Domains\Commerce\Payments\Data;

/**
 * Normalized webhook event parsed from a provider payload.
 */
final readonly class WebhookEvent
{
    /**
     * @param  string  $type  payment.succeeded | payment.failed | refund.succeeded
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $orderReference,
        public ?string $providerReference = null,
        public array $raw = [],
    ) {}
}
