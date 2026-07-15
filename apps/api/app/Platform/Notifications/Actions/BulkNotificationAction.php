<?php

namespace App\Platform\Notifications\Actions;

use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Services\NotificationDispatcher;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Fans a notification out to many users (each delivery is queued independently).
 */
class BulkNotificationAction extends BaseAction
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * @param  array<int, int>  $userIds
     * @param  array<string, mixed>  $data
     */
    public function executeForUserIds(array $userIds, NotificationCategory $category, string $templateKey, array $data = []): int
    {
        $count = 0;
        foreach ($userIds as $userId) {
            $this->dispatcher->dispatchToUserId($userId, $category, $templateKey, $data);
            $count++;
        }

        return $count;
    }
}
