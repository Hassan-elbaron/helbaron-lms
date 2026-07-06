<?php

namespace App\Domains\Commerce\Payments;

use App\Domains\Commerce\Contracts\PaymentGateway;
use App\Domains\Commerce\Payments\Gateways\FakeGateway;
use App\Domains\Commerce\Payments\Gateways\StripeGateway;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpClient;

/**
 * Resolves the configured PaymentGateway (fake | stripe) from config/commerce.php. The Stripe
 * adapter receives services.stripe config here so no other code reads vendor secrets.
 */
class GatewayManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): PaymentGateway
    {
        return match ((string) config('commerce.payment.provider', 'fake')) {
            'stripe' => new StripeGateway($this->app->make(HttpClient::class), (array) config('services.stripe')),
            default => $this->app->make(FakeGateway::class),
        };
    }
}
