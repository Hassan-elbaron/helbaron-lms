<?php

namespace App\Platform\Navigation\Rules;

use App\Platform\Navigation\Support\NavUrl;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule enforcing the nav URL-safety policy (NavUrl). Data-aware so it can read the
 * sibling `url_type` and validate accordingly: internal links must be site-relative/anchors,
 * external links must be http(s), and dangerous schemes (javascript:, data:, ...) are always
 * rejected. Reused by the FormRequest and the Filament item form so write paths agree.
 */
class SafeUrl implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /** @param  array<string, mixed>  $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $type = $this->data['url_type'] ?? 'internal';

        if (! is_string($value) || ! NavUrl::isSafe(is_string($type) ? $type : 'internal', $value)) {
            $fail('The :attribute is not a safe or valid link for its type.');
        }
    }
}
