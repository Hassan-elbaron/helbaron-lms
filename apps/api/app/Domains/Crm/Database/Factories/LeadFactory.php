<?php

namespace App\Domains\Crm\Database\Factories;

use App\Domains\Crm\Enums\LeadStatus;
use App\Domains\Crm\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'source' => fake()->randomElement(['web', 'referral', 'event']),
            'status' => LeadStatus::New->value,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn () => ['status' => LeadStatus::Converted->value]);
    }
}
