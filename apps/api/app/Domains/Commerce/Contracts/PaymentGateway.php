<?php

namespace App\Domains\Commerce\Contracts;

use App\Domains\Commerce\Payments\Data\ChargeRequest;
use App\Domains\Commerce\Payments\Data\ChargeResult;
use App\Domains\Commerce\Payments\Data\RefundRequest;
use App\Domains\Commerce\Payments\Data\RefundResult;
use App\Domains\Commerce\Payments\Data\WebhookEvent;

/**
 * Provider-agnostic payment gateway. Only concrete adapters reference a vendor SDK — commerce
 * code depends on this contract, never on Stripe directly.
 */
interface PaymentGateway
{
    public function charge(ChargeRequest $request): ChargeResult;

    public function refund(RefundRequest $request): RefundResult;

    /** Verify the signature and parse a raw webhook payload into a normalized event. */
    public function parseWebhook(string $payload, ?string $signature): WebhookEvent;
}
