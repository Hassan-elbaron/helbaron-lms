<?php

namespace App\Domains\Live\Enums;

enum MeetingProviderType: string
{
    case Fake = 'fake';
    case Zoom = 'zoom';
    case Teams = 'teams';
    case GoogleMeet = 'google_meet';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
