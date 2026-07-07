<?php

namespace App\Platform\Notifications\Actions;

use App\Platform\Notifications\Models\Notification;
use App\Platform\Shared\Actions\BaseAction;

class MarkNotificationReadAction extends BaseAction
{
    public function execute(Notification $notification): Notification
    {
        return $this->transaction(function () use ($notification): Notification {
            if ($notification->read_at === null) {
                $notification->forceFill(['read_at' => now()])->save();
            }

            return $notification;
        });
    }
}
