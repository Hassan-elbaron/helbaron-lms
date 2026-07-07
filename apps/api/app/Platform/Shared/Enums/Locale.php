<?php

namespace App\Platform\Shared\Enums;

/**
 * Supported UI/content locales for the bilingual platform.
 */
enum Locale: string
{
    case En = 'en';
    case Ar = 'ar';

    public function label(): string
    {
        return match ($this) {
            self::En => 'English',
            self::Ar => 'العربية',
        };
    }

    /** Text direction for this locale. */
    public function direction(): string
    {
        return $this === self::Ar ? 'rtl' : 'ltr';
    }

    public function isRtl(): bool
    {
        return $this->direction() === 'rtl';
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
