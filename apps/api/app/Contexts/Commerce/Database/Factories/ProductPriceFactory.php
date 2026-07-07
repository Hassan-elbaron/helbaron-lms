<?php

namespace App\Contexts\Commerce\Database\Factories;

use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'currency' => 'SAR',
            'amount_minor' => fake()->numberBetween(5000, 50000),
            'is_default' => true,
        ];
    }

    public function onSale(int $saleMinor): static
    {
        return $this->state(fn () => [
            'sale_amount_minor' => $saleMinor,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);
    }
}
