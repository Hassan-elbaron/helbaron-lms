<?php

use App\Contexts\Commerce\Actions\Payment\RefundOrderAction;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Payments\Gateways\FakeGateway;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/CommerceHelpers.php';

it('refunds a fulfilled order and revokes the enrollment', function () {
    [$course, $product] = courseProduct();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product' => $product->public_id])->assertOk();
    $checkout = $this->postJson('/api/v1/checkout')->assertCreated();
    $orderId = $checkout->json('data.order.id');
    $this->postJson("/api/v1/contracts/{$checkout->json('data.contract_id')}/accept")->assertOk();

    $payload = json_encode(['id' => 'evt_'.uniqid(), 'type' => 'payment.succeeded', 'order_reference' => $orderId]);
    $this->call('POST', '/api/v1/payment/webhook', [], [], [], ['HTTP_X-Signature' => FakeGateway::sign($payload)], $payload)->assertOk();

    expect(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'active')->exists())->toBeTrue();

    $order = Order::where('public_id', $orderId)->first();
    app(RefundOrderAction::class)->execute($order);

    expect($order->fresh()->status->value)->toBe('refunded')
        ->and(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'cancelled')->exists())->toBeTrue();
});
