<?php

use App\Domains\Commerce\Models\Order;
use App\Domains\Commerce\Payments\Gateways\FakeGateway;
use App\Platform\Identity\Models\User;
use App\Domains\Learning\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/CommerceHelpers.php';

function webhookPaid(string $orderPublicId): array
{
    $payload = json_encode(['id' => 'evt_'.uniqid(), 'type' => 'payment.succeeded', 'order_reference' => $orderPublicId]);

    return [$payload, FakeGateway::sign($payload)];
}

it('grants enrollment only after BOTH payment and contract acceptance', function () {
    [$course, $product] = courseProduct();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product' => $product->public_id])->assertOk();
    $checkout = $this->postJson('/api/v1/checkout')->assertCreated();
    $orderId = $checkout->json('data.order.id');
    $contractId = $checkout->json('data.contract_id');

    // Not paid, not accepted -> no enrollment.
    expect(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();

    // Accept contract while still unpaid -> still no enrollment.
    $this->postJson("/api/v1/contracts/{$contractId}/accept")->assertOk();
    expect(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();

    // Pay via webhook -> now fulfilled -> enrollment exists.
    [$payload, $sig] = webhookPaid($orderId);
    $this->call('POST', '/api/v1/payment/webhook', [], [], [], ['HTTP_X-Signature' => $sig], $payload)->assertOk();

    expect(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'active')->exists())->toBeTrue();
    expect(Order::where('public_id', $orderId)->first()->fulfilled_at)->not->toBeNull();
});

it('is idempotent when the same webhook is delivered twice', function () {
    [$course, $product] = courseProduct();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product' => $product->public_id])->assertOk();
    $checkout = $this->postJson('/api/v1/checkout')->assertCreated();
    $orderId = $checkout->json('data.order.id');
    $this->postJson("/api/v1/contracts/{$checkout->json('data.contract_id')}/accept")->assertOk();

    $payload = json_encode(['id' => 'evt_dupe', 'type' => 'payment.succeeded', 'order_reference' => $orderId]);
    $sig = FakeGateway::sign($payload);

    $this->call('POST', '/api/v1/payment/webhook', [], [], [], ['HTTP_X-Signature' => $sig], $payload)->assertOk();
    $this->call('POST', '/api/v1/payment/webhook', [], [], [], ['HTTP_X-Signature' => $sig], $payload)->assertOk();

    expect(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->count())->toBe(1);
});
