<?php

namespace App\Domains\Identity\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class UpdateProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'in:en,ar'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,unspecified'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
