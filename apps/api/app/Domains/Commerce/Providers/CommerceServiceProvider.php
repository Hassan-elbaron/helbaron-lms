<?php

namespace App\Domains\Commerce\Providers;

use App\Domains\Commerce\Contracts\PaymentGateway;
use App\Domains\Commerce\Events\ContractAccepted;
use App\Domains\Commerce\Events\OrderPaid;
use App\Domains\Commerce\Events\OrderRefunded;
use App\Domains\Commerce\Listeners\FulfillOnContractAccepted;
use App\Domains\Commerce\Listeners\FulfillOnOrderPaid;
use App\Domains\Commerce\Listeners\RevokeEnrollmentsOnRefund;
use App\Domains\Commerce\Models\Contract;
use App\Domains\Commerce\Models\Order;
use App\Domains\Commerce\Models\Product;
use App\Domains\Commerce\Payments\GatewayManager;
use App\Domains\Commerce\Policies\ContractPolicy;
use App\Domains\Commerce\Policies\OrderPolicy;
use App\Domains\Commerce\Policies\ProductPolicy;
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
