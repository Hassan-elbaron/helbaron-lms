<?php

namespace App\Platform\Identity\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class ForgotPasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
