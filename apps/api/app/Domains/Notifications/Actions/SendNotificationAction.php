<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\Channel;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Services\NotificationDispatcher;
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
