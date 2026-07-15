<?php

namespace App\Platform\Seo\Rules;

use App\Platform\Seo\Services\SeoResolver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects an unsafe/invalid canonical on write (Filament + any FormRequest), delegating to the ONE
 * canonical-safety definition in SeoResolver::isValidCanonical (absolute http(s) or a site-relative
 * path; never a dangerous scheme). Nulls pass — canonical is optional and defaults to the entity URL.
 */
class ValidCanonical implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! app(SeoResolver::class)->isValidCanonical($value)) {
            $fail('The canonical must be an absolute http(s) URL or a site-relative path.');
        }
    }
}
