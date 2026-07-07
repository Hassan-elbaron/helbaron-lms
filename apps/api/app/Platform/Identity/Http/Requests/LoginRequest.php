<?php

namespace App\Platform\Identity\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'mfa_code' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
