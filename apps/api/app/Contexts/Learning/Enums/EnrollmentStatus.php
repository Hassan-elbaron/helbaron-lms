<?php

namespace App\Contexts\Learning\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
