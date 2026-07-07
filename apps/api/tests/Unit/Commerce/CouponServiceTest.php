<?php

use App\Contexts\Commerce\Models\Coupon;
use App\Contexts\Commerce\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

it('computes percentage and fixed discounts', function () {
    $lines = new Collection([
        ['product_id' => 1, 'amount_minor' => 10000],
        ['product_id' => 2, 'amount_minor' => 5000],
    ]);

    $pct = Coupon::factory()->percentage(10)->create();
    $fixed = Coupon::factory()->fixed(2000)->create();

    $svc = new CouponService;
    expect($svc->discountMinor($pct, $lines))->toBe(1500)   // 10% of 15000
        ->and($svc->discountMinor($fixed, $lines))->toBe(2000);
});
