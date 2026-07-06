<?php

namespace App\Domains\Analytics\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

class KpiQueryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'metrics' => ['required', 'array', 'min:1'],
            'metrics.*' => ['string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}
