<?php

namespace App\Platform\Seo\Rules;

use App\Platform\Seo\Services\SeoResolver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects malformed JSON-LD on write so only a valid structured-data document (an object/array that
 * decodes cleanly) is ever stored and later emitted. Nulls/blank pass — JSON-LD is optional.
 * Delegates to SeoResolver::isValidJsonLd (single definition, reused by the resolver's emit guard).
 */
class ValidJsonLd implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || (is_string($value) && trim($value) === '') || $value === []) {
            return;
        }

        if (! app(SeoResolver::class)->isValidJsonLd($value)) {
            $fail('The JSON-LD must be a valid JSON object or array.');
        }
    }
}
