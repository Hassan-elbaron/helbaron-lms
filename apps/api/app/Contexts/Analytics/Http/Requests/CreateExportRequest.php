<?php

namespace App\Contexts\Analytics\Http\Requests;

use App\Contexts\Analytics\Enums\ExportFormat;
use App\Platform\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateExportRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'report' => ['required', 'string'],
            'format' => ['required', Rule::in(ExportFormat::values())],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }
}
