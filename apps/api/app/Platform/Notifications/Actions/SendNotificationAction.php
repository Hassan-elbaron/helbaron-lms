<?php

namespace App\Platform\Notifications\Actions;

use App\Platform\Identity\Models\User;
use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Models\Notification;
use App\Platform\Notifications\Services\NotificationDispatcher;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Sends a single notification to a user (queued delivery via the dispatcher).
 */
class SendNotificationAction extends BaseAction
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, Channel>|null  $channels
     */
    public function execute(User $user, NotificationCategory $category, string $templateKey, array $data = [], ?array $channels = null): Notification
    {
        return $this->dispatcher->dispatch($user, $category, $templateKey, $data, $channels);
    }
}
