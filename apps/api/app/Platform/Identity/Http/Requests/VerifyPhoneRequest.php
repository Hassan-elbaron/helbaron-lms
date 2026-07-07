<?php

namespace App\Platform\Identity\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class VerifyPhoneRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}
