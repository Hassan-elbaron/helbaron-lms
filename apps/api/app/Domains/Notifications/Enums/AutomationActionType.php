<?php

namespace App\Domains\Notifications\Enums;

enum AutomationActionType: string
{
    case SendNotification = 'send_notification';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
