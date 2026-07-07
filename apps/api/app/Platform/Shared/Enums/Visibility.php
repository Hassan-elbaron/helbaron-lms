<?php

namespace App\Platform\Shared\Enums;

/**
 * Generic visibility for resources (used later by catalog/content domains).
 */
enum Visibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Hidden = 'hidden';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isPublic(): bool
    {
        return $this === self::Public;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
