<?php

namespace App\Platform\Notifications\Actions;

use App\Platform\Notifications\Models\Notification;
use App\Platform\Shared\Actions\BaseAction;

class ArchiveNotificationAction extends BaseAction
{
    public function execute(Notification $notification): Notification
    {
        return $this->transaction(function () use ($notification): Notification {
            $notification->forceFill([
                'archived_at' => now(),
                'read_at' => $notification->read_at ?? now(),
            ])->save();

            return $notification;
        });
    }
}
