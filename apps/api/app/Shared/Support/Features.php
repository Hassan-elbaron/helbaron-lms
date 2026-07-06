<?php

namespace App\Shared\Support;

/**
 * Tiny feature-flag reader over config/features.php. Unknown flags are treated as disabled.
 */
final class Features
{
    public static function enabled(string $flag): bool
    {
        return (bool) config("features.flags.{$flag}", false);
    }

    public static function disabled(string $flag): bool
    {
        return ! self::enabled($flag);
    }
}
