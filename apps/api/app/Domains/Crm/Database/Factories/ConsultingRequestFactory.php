<?php

namespace App\Domains\Crm\Database\Factories;

use App\Domains\Crm\Enums\ConsultingRequestStatus;
use App\Domains\Crm\Models\ConsultingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultingRequest>
 */
class ConsultingRequestFactory extends Factory
{
    protected $model = ConsultingRequest::class;

    public function definition(): array
    {
        return [
            'subject' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->paragraph(),
            'status' => ConsultingRequestStatus::New->value,
            'sla_due_at' => now()->addHours(48),
        ];
    }
}
