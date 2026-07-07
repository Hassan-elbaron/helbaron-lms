<?php

namespace App\Domains\Crm\Database\Factories;

use App\Domains\Crm\Enums\OrganizationStatus;
use App\Domains\Crm\Models\Organization;
use App\Platform\Shared\Helpers\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Slug::make($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'status' => OrganizationStatus::Active->value,
            'size' => 'medium',
            'website' => fake()->url(),
        ];
    }
}
