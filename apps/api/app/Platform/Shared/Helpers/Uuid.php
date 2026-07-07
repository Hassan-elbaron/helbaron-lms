<?php

namespace App\Platform\Shared\Helpers;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid as RamseyUuid;

/**
 * UUID helper. Prefers time-ordered UUIDv7 for index locality, with safe fallbacks across
 * framework/library versions.
 */
final class Uuid
{
    /** Generate a time-ordered UUID (v7 when available). */
    public static function v7(): string
    {
        if (method_exists(Str::class, 'uuid7')) {
            return (string) Str::uuid7();
        }

        if (class_exists(RamseyUuid::class) && method_exists(RamseyUuid::class, 'uuid7')) {
            return RamseyUuid::uuid7()->toString();
        }

        return (string) Str::orderedUuid();
    }

    /** Generate a random UUID (v4). */
    public static function v4(): string
    {
        return (string) Str::uuid();
    }

    public static function isValid(string $value): bool
    {
        return Str::isUuid($value);
    }
}
