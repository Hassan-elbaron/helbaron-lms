<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\Notifications\Models\Notification;
use App\Shared\Actions\BaseAction;

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
