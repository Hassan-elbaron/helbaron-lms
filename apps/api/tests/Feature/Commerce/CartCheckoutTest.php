<?php

use App\Domains\Commerce\Models\Coupon;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/CommerceHelpers.php';

it('adds to cart, shows totals, and checks out to a pending order', function () {
    [$course, $product] = courseProduct(19900);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product' => $product->public_id])
        ->assertOk()->assertJsonPath('data.total_minor', 19900);

    $this->getJson('/api/v1/cart')->assertOk()->assertJsonPath('data.subtotal_minor', 19900);

    $checkout = $this->postJson('/api/v1/checkout')->assertCreated();
    expect($checkout->json('data.order.status'))->toBe('pending')
        ->and($checkout->json('data.contract_id'))->toBeString()
        ->and($checkout->json('data.payment.provider_reference'))->toBeString();
});

it('applies a coupon to reduce the total', function () {
    [$course, $product] = courseProduct(20000);
    $coupon = Coupon::factory()->percentage(25)->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product' => $product->public_id, 'coupon_code' => $coupon->code])
        ->assertOk()->assertJsonPath('data.discount_minor', 5000)->assertJsonPath('data.total_minor', 15000);
});
