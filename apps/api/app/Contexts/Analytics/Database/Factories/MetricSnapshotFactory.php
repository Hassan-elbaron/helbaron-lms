<?php

namespace App\Contexts\Analytics\Database\Factories;

use App\Contexts\Analytics\Models\MetricSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetricSnapshot>
 */
class MetricSnapshotFactory extends Factory
{
    protected $model = MetricSnapshot::class;

    public function definition(): array
    {
        return [
            'metric_key' => 'enrollments',
            'granularity' => 'daily',
            'period' => now()->toDateString(),
            'dimension_key' => '',
            'dimension_value' => '',
            'value' => fake()->numberBetween(1, 100),
        ];
    }
}
