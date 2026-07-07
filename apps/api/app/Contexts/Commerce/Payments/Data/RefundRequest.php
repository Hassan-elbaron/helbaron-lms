<?php

namespace App\Contexts\Commerce\Payments\Data;

final readonly class RefundRequest
{
    public function __construct(
        public string $providerReference,
        public int $amountMinor,
        public string $currency,
    ) {}
}
