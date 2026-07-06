<?php

namespace App\Domains\Notifications\Enums;

enum AutomationTriggerType: string
{
    case Event = 'event';
    case Scheduled = 'scheduled';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
