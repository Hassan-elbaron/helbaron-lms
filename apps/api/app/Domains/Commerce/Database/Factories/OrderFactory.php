<?php

namespace App\Domains\Commerce\Database\Factories;

use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Commerce\Models\Order;
use App\Platform\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(5000, 50000);

        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending->value,
            'currency' => 'SAR',
            'subtotal_minor' => $total,
            'discount_minor' => 0,
            'total_minor' => $total,
            'placed_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Paid->value, 'paid_at' => now()]);
    }
}
