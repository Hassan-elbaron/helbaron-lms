<?php

namespace App\Domains\Identity\Enums;

/**
 * Canonical role names (backed by spatie/laravel-permission rows created in the seeder).
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Instructor = 'instructor';
    case Student = 'student';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
