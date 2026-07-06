<?php

namespace App\Domains\Commerce\Payments\Data;

final readonly class ChargeResult
{
    /**
     * @param  string  $status  pending | succeeded | failed
     */
    public function __construct(
        public string $providerReference,
        public string $status,
        public ?string $clientSecret = null,
        public ?string $redirectUrl = null,
    ) {}

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
