<?php

namespace App\Platform\Notifications\Enums;

enum Channel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Sms = 'sms';
    case Push = 'push';
    case WhatsApp = 'whatsapp';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
