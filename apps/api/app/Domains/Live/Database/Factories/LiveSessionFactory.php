<?php

namespace App\Domains\Live\Database\Factories;

use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Models\LiveSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSession>
 */
class LiveSessionFactory extends Factory
{
    protected $model = LiveSession::class;

    public function definition(): array
    {
        $start = now()->addDays(3)->setTime(14, 0);

        return [
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->paragraph(),
            'status' => LiveSessionStatus::Scheduled->value,
            'timezone' => 'Asia/Riyadh',
            'starts_at' => $start,
            'ends_at' => (clone $start)->addHour(),
            'capacity' => null,
            'waiting_room' => true,
        ];
    }

    public function capacity(int $capacity): static
    {
        return $this->state(fn () => ['capacity' => $capacity]);
    }

    public function startingSoon(): static
    {
        return $this->state(fn () => ['starts_at' => now()->addMinutes(5), 'ends_at' => now()->addHour()]);
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'status' => LiveSessionStatus::Live->value,
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(55),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => LiveSessionStatus::Cancelled->value]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => LiveSessionStatus::Completed->value,
            'starts_at' => now()->subDays(2)->setTime(14, 0),
            'ends_at' => now()->subDays(2)->setTime(15, 0),
        ]);
    }
}
