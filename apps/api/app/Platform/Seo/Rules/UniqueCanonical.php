<?php

namespace App\Platform\Seo\Rules;

use App\Platform\Seo\Models\SeoMeta;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Duplicate-canonical guard: fails when another seo_metas row already declares the same canonical
 * URL, so two managed surfaces cannot claim one canonical (a classic duplicate-content mistake). The
 * current record is excluded via $ignoreId on edit. Nulls pass — an absent canonical defaults to the
 * entity URL and is not compared here.
 */
class UniqueCanonical implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $exists = SeoMeta::query()
            ->where('canonical', trim($value))
            ->when($this->ignoreId !== null, fn ($q) => $q->whereKeyNot($this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('Another SEO record already uses this canonical URL.');
        }
    }
}
