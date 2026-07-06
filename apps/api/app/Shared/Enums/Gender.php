<?php

namespace App\Shared\Enums;

/**
 * Generic gender value (used later by profile-style entities). No business logic.
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Unspecified = 'unspecified';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
