<?php

namespace App\Contexts\Commerce\Providers;

use App\Contexts\Commerce\Contracts\PaymentGateway;
use App\Contexts\Commerce\Events\ContractAccepted;
use App\Contexts\Commerce\Events\OrderPaid;
use App\Contexts\Commerce\Events\OrderRefunded;
use App\Contexts\Commerce\Listeners\FulfillOnContractAccepted;
use App\Contexts\Commerce\Listeners\FulfillOnOrderPaid;
use App\Contexts\Commerce\Listeners\RevokeEnrollmentsOnRefund;
use App\Contexts\Commerce\Models\Contract;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Payments\GatewayManager;
use App\Contexts\Commerce\Policies\ContractPolicy;
use App\Contexts\Commerce\Policies\OrderPolicy;
use App\Contexts\Commerce\Policies\ProductPolicy;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * Wires the Commerce module: config, migrations, routes, policies, the PaymentGateway binding
 * (Fake default, never Stripe directly), and the fulfillment/refund listeners.
 */
class CommerceServiceProvider extends BaseDomainServiceProvider
{
    protected array $routeFiles = ['routes/commerce.php'];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        Order::class => OrderPolicy::class,
        Contract::class => ContractPolicy::class,
        Product::class => ProductPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/commerce.php', 'commerce');

        // Payment code depends only on the abstraction; the concrete gateway comes from config.
        $this->app->bind(PaymentGateway::class, fn ($app) => $app->make(GatewayManager::class)->resolve());
    }

    protected function bootDomain(): void
    {
        // Fulfillment is gated on BOTH payment and contract acceptance.
        Event::listen(OrderPaid::class, FulfillOnOrderPaid::class);
        Event::listen(ContractAccepted::class, FulfillOnContractAccepted::class);
        Event::listen(OrderRefunded::class, RevokeEnrollmentsOnRefund::class);
    }
}
