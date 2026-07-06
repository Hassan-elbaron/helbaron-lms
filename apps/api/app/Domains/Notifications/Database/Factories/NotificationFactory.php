<?php

namespace App\Domains\Notifications\Database\Factories;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => NotificationCategory::System->value,
            'type' => 'generic',
            'title' => rtrim(fake()->sentence(4), '.'),
            'body' => fake()->sentence(),
            'data' => [],
            'locale' => 'en',
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
