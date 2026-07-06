<?php

namespace App\Domains\Analytics\Database\Factories;

use App\Domains\Analytics\Enums\ReportType;
use App\Domains\Analytics\Models\ReportDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinition>
 */
class ReportDefinitionFactory extends Factory
{
    protected $model = ReportDefinition::class;

    public function definition(): array
    {
        return [
            'name' => rtrim(fake()->sentence(3), '.'),
            'type' => ReportType::Metric->value,
            'metric_keys' => ['enrollments', 'completions'],
            'filters' => [],
            'visibility' => 'shared',
        ];
    }
}
