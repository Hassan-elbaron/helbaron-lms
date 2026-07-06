<?php

namespace App\Domains\Crm\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

class ConsultingRequestRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'organization' => ['nullable', 'string'], // organization public_id
        ];
    }
}
