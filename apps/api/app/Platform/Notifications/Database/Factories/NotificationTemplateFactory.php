<?php

namespace App\Platform\Notifications\Database\Factories;

use App\Platform\Notifications\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'key' => 'welcome',
            'channel' => 'in_app',
            'locale' => 'en',
            'subject' => 'Welcome, {{ name }}',
            'body' => 'Hello {{ name }}, welcome to HElbaron.',
            'is_active' => true,
        ];
    }
}
