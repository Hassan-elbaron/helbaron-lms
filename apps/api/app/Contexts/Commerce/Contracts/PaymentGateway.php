<?php

namespace App\Contexts\Commerce\Contracts;

use App\Contexts\Commerce\Payments\Data\ChargeRequest;
use App\Contexts\Commerce\Payments\Data\ChargeResult;
use App\Contexts\Commerce\Payments\Data\RefundRequest;
use App\Contexts\Commerce\Payments\Data\RefundResult;
use App\Contexts\Commerce\Payments\Data\WebhookEvent;

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
