<?php

namespace App\Platform\Identity\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

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
