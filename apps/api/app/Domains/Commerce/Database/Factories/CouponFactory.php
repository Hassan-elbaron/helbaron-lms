<?php

namespace App\Domains\Commerce\Database\Factories;

use App\Domains\Commerce\Enums\CouponScope;
use App\Domains\Commerce\Enums\CouponType;
use App\Domains\Commerce\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'type' => CouponType::Percentage->value,
            'value' => 10,
            'scope' => CouponScope::All->value,
            'is_active' => true,
        ];
    }

    public function fixed(int $minor, string $currency = 'SAR'): static
    {
        return $this->state(fn () => [
            'type' => CouponType::Fixed->value,
            'value' => $minor,
            'currency' => $currency,
        ]);
    }

    public function percentage(int $percent): static
    {
        return $this->state(fn () => ['type' => CouponType::Percentage->value, 'value' => $percent]);
    }
}
