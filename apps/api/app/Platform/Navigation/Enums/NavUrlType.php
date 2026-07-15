<?php

namespace App\Platform\Navigation\Enums;

/**
 * How a nav item's URL is interpreted (and therefore validated). `internal` is an in-app relative
 * path ("/courses") or in-page anchor ("#section"); `external` is an absolute http(s) URL. The
 * URL-safety rules (App\Platform\Navigation\Support\NavUrl) differ per type.
 */
enum NavUrlType: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal (relative path)',
            self::External => 'External (https URL)',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }

    /** @return array<string, string> value => label. */
    public static function options(): array
    {
        return [
            self::Internal->value => self::Internal->label(),
            self::External->value => self::External->label(),
        ];
    }
}
