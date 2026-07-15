<?php

namespace App\Platform\Navigation\Http\Requests;

use App\Platform\Navigation\Enums\NavAuthVisibility;
use App\Platform\Navigation\Enums\NavUrlType;
use App\Platform\Navigation\Rules\SafeUrl;
use App\Platform\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating/updating a nav item. The URL-safety policy (SafeUrl / NavUrl) is enforced
 * here so no unsafe href can ever be persisted — the same rule the Filament item form applies. All
 * admin mutation flows through validation that agrees on what a safe link is.
 */
class NavItemRequest extends BaseFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'array'],
            'label.en' => ['required', 'string', 'max:120'],
            'label.ar' => ['nullable', 'string', 'max:120'],
            'url_type' => ['required', Rule::in(NavUrlType::values())],
            'url' => ['required', 'string', 'max:2048', new SafeUrl],
            'icon' => ['nullable', 'string', 'max:64'],
            'parent_id' => ['nullable', 'integer'],
            'position' => ['nullable', 'integer'],
            'is_enabled' => ['boolean'],
            'open_new_tab' => ['boolean'],
            'rel' => ['nullable', 'string', 'max:120'],
            'badge' => ['nullable', 'array'],
            'description' => ['nullable', 'array'],
            'image' => ['nullable', 'string', 'max:2048'],
            'visibility_roles' => ['nullable', 'array'],
            'visibility_roles.*' => ['string', 'max:64'],
            'visibility_auth' => ['nullable', Rule::in(NavAuthVisibility::values())],
            'visibility_locales' => ['nullable', 'array'],
            'visibility_locales.*' => [Rule::in(['en', 'ar'])],
            'feature_flag' => ['nullable', 'string', 'max:120'],
        ];
    }
}
