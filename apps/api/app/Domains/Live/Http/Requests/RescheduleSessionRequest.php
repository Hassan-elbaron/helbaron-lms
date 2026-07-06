<?php

namespace App\Domains\Live\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

class RescheduleSessionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ];
    }
}
