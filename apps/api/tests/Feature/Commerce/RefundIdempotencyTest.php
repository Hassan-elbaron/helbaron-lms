<?php

use App\Contexts\Commerce\Actions\Payment\RefundOrderAction;
use App\Contexts\Commerce\Enums\TransactionType;
use App\Contexts\Commerce\Exceptions\OrderNotRefundableException;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Payments\Gateways\FakeGateway;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Audit\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/CommerceHelpers.php';

function paidOrder(): Order
{
    [, $product] = courseProduct();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    test()->postJson('/api/v1/cart', ['product' => $product->public_id])->assertOk();
    $checkout = test()->postJson('/api/v1/checkout')->assertCreated();
    $orderId = $checkout->json('data.order.id');
    test()->postJson("/api/v1/contracts/{$checkout->json('data.contract_id')}/accept")->assertOk();

    $payload = json_encode(['id' => 'evt_'.uniqid(), 'type' => 'payment.succeeded', 'order_reference' => $orderId]);
    test()->call('POST', '/api/v1/payment/webhook', [], [], [], ['HTTP_X-Signature' => FakeGateway::sign($payload)], $payload)->assertOk();

    return Order::where('public_id', $orderId)->firstOrFail();
}

it('refuses to refund the same order twice', function () {
    $order = paidOrder();

    app(RefundOrderAction::class)->execute($order);
    expect($order->fresh()->status->value)->toBe('refunded');

    app(RefundOrderAction::class)->execute($order->fresh());
})->throws(OrderNotRefundableException::class);

it('records exactly one refund transaction and an audit entry', function () {
    $order = paidOrder();

    app(RefundOrderAction::class)->execute($order);

    try {
        app(RefundOrderAction::class)->execute($order->fresh());
    } catch (OrderNotRefundableException) {
        // expected: second attempt is rejected
    }

    expect($order->fresh()->transactions()->where('type', TransactionType::Refund->value)->count())->toBe(1)
        ->and(AuditLog::where('action', 'order.refunded')->where('subject_id', $order->id)->count())->toBe(1);
});

it('refuses to refund an unpaid order', function () {
    [, $product] = courseProduct();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    test()->postJson('/api/v1/cart', ['product' => $product->public_id])->assertOk();
    $checkout = test()->postJson('/api/v1/checkout')->assertCreated();
    $order = Order::where('public_id', $checkout->json('data.order.id'))->firstOrFail();

    app(RefundOrderAction::class)->execute($order);
})->throws(OrderNotRefundableException::class);
