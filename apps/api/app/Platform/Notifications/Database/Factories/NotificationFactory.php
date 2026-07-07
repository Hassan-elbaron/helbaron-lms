<?php

namespace App\Platform\Notifications\Database\Factories;

use App\Platform\Identity\Models\User;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Models\Notification;
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
