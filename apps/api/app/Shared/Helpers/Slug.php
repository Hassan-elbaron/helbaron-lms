<?php

namespace App\Shared\Helpers;

use Illuminate\Support\Str;

/**
 * Slug helper. Wraps Str::slug with a language hint and an optional uniqueness resolver.
 */
final class Slug
{
    public static function make(string $value, string $language = 'en'): string
    {
        return Str::slug($value, '-', $language);
    }

    /**
     * Produce a unique slug by suffixing -2, -3, ... using the provided existence check.
     *
     * @param  callable(string): bool  $exists  returns true if the candidate slug is taken
     */
    public static function unique(string $value, callable $exists, string $language = 'en'): string
    {
        $base = self::make($value, $language);
        $slug = $base;
        $i = 2;

        while ($exists($slug)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
