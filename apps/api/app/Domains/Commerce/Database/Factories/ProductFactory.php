<?php

namespace App\Domains\Commerce\Database\Factories;

use App\Domains\Commerce\Enums\ProductStatus;
use App\Domains\Commerce\Enums\ProductType;
use App\Domains\Commerce\Models\Product;
use App\Platform\Shared\Helpers\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'type' => ProductType::Course->value,
            'title' => $title,
            'slug' => Slug::make($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::Active->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft->value]);
    }
}
