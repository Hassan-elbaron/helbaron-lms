<?php

namespace App\Platform\Notifications\Http\Requests;

use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Enums\DigestFrequency;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'in:en,ar'],
            'digest_frequency' => ['sometimes', Rule::in(DigestFrequency::values())],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*.category' => ['required_with:preferences', Rule::in(NotificationCategory::values())],
            'preferences.*.channel' => ['required_with:preferences', Rule::in(Channel::values())],
            'preferences.*.enabled' => ['required_with:preferences', 'boolean'],
        ];
    }
}
