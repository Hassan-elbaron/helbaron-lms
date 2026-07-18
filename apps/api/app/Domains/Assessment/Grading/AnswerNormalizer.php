<?php

namespace App\Domains\Assessment\Grading;

/**
 * Normalises free-text answers before comparison.
 *
 * "Exact match" on raw strings fails learners for reasons that have nothing to do with knowledge —
 * a trailing space, a curly apostrophe, an Arabic أ typed as ا. This class defines exactly what
 * counts as the same answer, and every text-matching grader routes through it so the rules can
 * never drift apart between types.
 *
 * Arabic handling matters here: the platform is bilingual, and Arabic learners routinely type
 * without diacritics and with interchangeable alef/ya/ta-marbuta forms. Treating those as
 * different answers would fail correct responses.
 *
 * Authors can opt out per question via `config`: {"case_sensitive": true, "normalize_arabic": false}.
 */
class AnswerNormalizer
{
    /** Alef variants (آ أ إ ٱ) → ا, ya (ى) → ي, ta marbuta (ة) → ه. */
    private const ARABIC_LETTER_MAP = [
        "\u{0622}" => "\u{0627}", "\u{0623}" => "\u{0627}", "\u{0625}" => "\u{0627}", "\u{0671}" => "\u{0627}",
        "\u{0649}" => "\u{064A}",
        "\u{0629}" => "\u{0647}",
    ];

    /** Harakat + tatweel — decorative in typed input, never meaning-bearing for an exam answer. */
    private const ARABIC_DIACRITICS = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06DC}\x{0640}]/u';

    /** Typographic quotes/dashes that word processors substitute silently. */
    private const PUNCTUATION_MAP = [
        "\u{2018}" => "'", "\u{2019}" => "'", "\u{201C}" => '"', "\u{201D}" => '"',
        "\u{2013}" => '-', "\u{2014}" => '-',
    ];

    public function normalize(string $value, bool $caseSensitive = false, bool $normalizeArabic = true): string
    {
        // Arabic-Indic and Eastern Arabic-Indic digits → ASCII, so "٤٢" matches "42".
        $value = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $value = strtr($value, self::PUNCTUATION_MAP);

        if ($normalizeArabic) {
            $value = (string) preg_replace(self::ARABIC_DIACRITICS, '', $value);
            $value = strtr($value, self::ARABIC_LETTER_MAP);
        }

        // Collapse every run of whitespace (including non-breaking and full-width) to one space.
        $value = (string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $value));
        $value = trim($value);

        return $caseSensitive ? $value : mb_strtolower($value, 'UTF-8');
    }

    /**
     * True when the learner's text matches ANY accepted value. Authors list accepted spellings as
     * separate options rather than relying on fuzzy matching, so the answer key stays explicit
     * and reviewable.
     *
     * @param  iterable<string>  $accepted
     */
    public function matchesAny(string $submitted, iterable $accepted, bool $caseSensitive = false, bool $normalizeArabic = true): bool
    {
        $needle = $this->normalize($submitted, $caseSensitive, $normalizeArabic);

        if ($needle === '') {
            return false;
        }

        foreach ($accepted as $candidate) {
            if ($this->normalize($candidate, $caseSensitive, $normalizeArabic) === $needle) {
                return true;
            }
        }

        return false;
    }
}
