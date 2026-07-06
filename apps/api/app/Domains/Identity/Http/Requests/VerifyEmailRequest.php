<?php

namespace App\Domains\Identity\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

class VerifyEmailRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}
