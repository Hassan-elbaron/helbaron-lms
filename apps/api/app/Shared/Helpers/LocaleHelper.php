<?php

namespace App\Shared\Helpers;

use App\Shared\Enums\Locale;

/**
 * Locale helpers reading from config/shared.php. No business logic.
 */
final class LocaleHelper
{
    public static function current(): string
    {
        return (string) app()->getLocale();
    }

    public static function fallback(): string
    {
        return (string) config('shared.fallback_locale', 'en');
    }

    /** @return array<int, string> */
    public static function supported(): array
    {
        return (array) config('shared.locales', Locale::values());
    }

    public static function isRtl(string $locale): bool
    {
        return in_array($locale, (array) config('shared.rtl_locales', ['ar']), true);
    }

    public static function direction(string $locale): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }
}
