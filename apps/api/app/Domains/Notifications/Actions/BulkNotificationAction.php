<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Services\NotificationDispatcher;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Collection;

/**
 * Fans a notification out to many users (each delivery is queued independently).
 */
class BulkNotificationAction extends BaseAction
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    public function execute(Collection $users, NotificationCategory $category, string $templateKey, array $data = []): int
    {
        $count = 0;
        foreach ($users as $user) {
            $this->dispatcher->dispatch($user, $category, $templateKey, $data);
            $count++;
        }

        return $count;
    }
}
