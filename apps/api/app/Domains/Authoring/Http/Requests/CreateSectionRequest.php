<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class CreateSectionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
