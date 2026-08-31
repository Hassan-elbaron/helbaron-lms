<?php

namespace App\Platform\Shared\Support;

/**
 * Environment reads whose defaults survive a key that is present but empty.
 *
 * WHY THIS EXISTS. Laravel's `env('KEY', $default)` returns the *default* only when the key is
 * absent. A key written as `KEY=` in a `.env` file is PRESENT with the value `''`, so the default
 * never fires. `.env.example` shipped a dozen optional keys in exactly that form, which meant every
 * instance created the documented way (copy `.env.example`) silently received empty values where a
 * carefully-built fallback chain was supposed to run:
 *
 *   - `BRAND_NAME_AR=`        → blank Arabic brand name across the whole Arabic UI
 *   - `BRAND_COMPANY_NAME=`   → blank company name, which also feeds the certificate issuer
 *   - `SEED_ADMIN_PASSWORD=`  → the local dev admin was created with `Hash::make('')`, so nobody
 *                               could log into a fresh local install at all
 *
 * The same mistake in a different shape is recorded against `HasSlug` in the enhancement plan
 * ("`??` does not fire when the key exists but is empty — use `filled()` semantics"). This helper is
 * the single place that semantics now lives for environment reads.
 *
 * A value of `false` or `0` is NOT treated as absent — only null and whitespace-only strings are.
 * `LEARNING_PLAYBACK_ALLOW_FAKE=false` must keep meaning false, not fall through to a default of true.
 */
final class Env
{
    /**
     * Read an environment value, falling back when the key is absent OR present-but-blank.
     *
     * The fallback may be a closure so an expensive or recursive default (one that itself reads the
     * environment) is only evaluated when it is actually needed.
     */
    public static function orFallback(string $key, mixed $fallback = null): mixed
    {
        $value = env($key);

        if (self::isBlank($value)) {
            return $fallback instanceof \Closure ? $fallback() : $fallback;
        }

        return $value;
    }

    /** String form of orFallback(), for the many callers that immediately cast. */
    public static function string(string $key, mixed $fallback = ''): string
    {
        $value = self::orFallback($key, $fallback);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Absent for fallback purposes: null, or a string that is empty once trimmed.
     *
     * Deliberately NOT Laravel's `blank()`: blank() treats `false` and `[]` as blank too, and a
     * boolean env value of false is a real, intentional setting.
     */
    private static function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
