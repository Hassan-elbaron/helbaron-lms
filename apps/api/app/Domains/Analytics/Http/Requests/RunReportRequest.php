<?php

namespace App\Domains\Analytics\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class RunReportRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'report' => ['required', 'string'], // report public_id
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}
