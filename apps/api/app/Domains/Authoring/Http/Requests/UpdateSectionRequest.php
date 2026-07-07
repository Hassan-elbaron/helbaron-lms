<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class UpdateSectionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
