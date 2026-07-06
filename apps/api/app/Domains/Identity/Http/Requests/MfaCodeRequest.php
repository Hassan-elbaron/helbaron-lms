<?php

namespace App\Domains\Identity\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

/**
 * Shared request for MFA verify/disable (a single TOTP or recovery code).
 */
class MfaCodeRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}
