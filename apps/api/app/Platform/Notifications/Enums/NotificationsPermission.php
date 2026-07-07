<?php

namespace App\Platform\Notifications\Enums;

enum NotificationsPermission: string
{
    case ManageTemplates = 'notifications.templates.manage';
    case ManageAutomation = 'notifications.automation.manage';
    case ViewLogs = 'notifications.logs.view';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
