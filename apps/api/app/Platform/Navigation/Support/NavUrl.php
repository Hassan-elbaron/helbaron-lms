<?php

namespace App\Platform\Navigation\Support;

use App\Platform\Navigation\Enums\NavUrlType;

/**
 * URL SAFETY gate for nav items. The single source of truth for what counts as a safe link, used
 * in three places: the FormRequest (reject on write), the model accessor (defense-in-depth on
 * read), and the API resource (never emit an unsafe URL). No admin-supplied string is ever
 * rendered as an href without passing through here.
 *
 * Rules:
 *  - Dangerous schemes are rejected outright, anywhere: javascript:, data:, vbscript:, file:,
 *    blob:, plus any other "scheme:" that is not http/https (e.g. mailto:, tel: are NOT allowed
 *    as nav hrefs here — nav items are page links).
 *  - internal: must be a site-relative path ("/...", not protocol-relative "//") or an in-page
 *    anchor ("#..."). Bare "/" is allowed (home).
 *  - external: must be an absolute http(s):// URL.
 */
final class NavUrl
{
    /** Schemes that must never appear at the start of a URL, regardless of type. */
    private const DANGEROUS_SCHEMES = ['javascript', 'data', 'vbscript', 'file', 'blob'];

    /** True when $url is a safe href for the given url type. */
    public static function isSafe(NavUrlType|string $type, ?string $url): bool
    {
        $type = $type instanceof NavUrlType ? $type : NavUrlType::tryFrom($type);
        $value = trim((string) $url);

        if ($type === null || $value === '') {
            return false;
        }

        // Strip control/whitespace obfuscation (e.g. "java\tscript:") before scheme detection.
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', $value) ?? '');

        foreach (self::DANGEROUS_SCHEMES as $scheme) {
            if (str_starts_with($normalized, $scheme.':')) {
                return false;
            }
        }

        return match ($type) {
            NavUrlType::External => preg_match('#^https?://#i', $value) === 1,
            NavUrlType::Internal => self::isSafeInternal($value),
        };
    }

    /** Return $url when safe, otherwise a harmless "#" so hrefs are always renderable. */
    public static function sanitize(NavUrlType|string $type, ?string $url): string
    {
        return self::isSafe($type, $url) ? trim((string) $url) : '#';
    }

    private static function isSafeInternal(string $value): bool
    {
        if (str_starts_with($value, '//')) {
            return false; // protocol-relative — treated as external, unsafe for an internal link
        }

        return str_starts_with($value, '/') || str_starts_with($value, '#');
    }
}
