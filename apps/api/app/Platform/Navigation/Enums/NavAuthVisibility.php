<?php

namespace App\Platform\Navigation\Enums;

/**
 * Auth-state gating for a nav item. The API emits this as metadata; the frontend resolver decides
 * whether to render the item for the current visitor:
 *  - Any:           show to everyone (default).
 *  - Guest:         show only to unauthenticated visitors (e.g. Login / Register).
 *  - Authenticated: show only to signed-in users (e.g. Dashboard / Logout).
 */
enum NavAuthVisibility: string
{
    case Any = 'any';
    case Guest = 'guest';
    case Authenticated = 'authenticated';

    public function label(): string
    {
        return match ($this) {
            self::Any => 'Everyone',
            self::Guest => 'Guests only',
            self::Authenticated => 'Signed-in only',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $v) => $v->value, self::cases());
    }

    /** @return array<string, string> value => label. */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
