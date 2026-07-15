<?php

namespace App\Contexts\Analytics\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

/**
 * Query validation for the operational report endpoints: an optional [from, to] window and
 * pagination for the tabular reports. Admin authorization is enforced in the controller.
 */
class ReportInsightRequest extends BaseFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
