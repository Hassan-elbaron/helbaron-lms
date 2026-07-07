<?php

namespace App\Contexts\Commerce\Payments\Data;

final readonly class RefundResult
{
    public function __construct(
        public string $providerReference,
        public string $status, // succeeded | failed
    ) {}

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
