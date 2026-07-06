<?php

namespace App\Domains\Learning\Http\Requests;

use App\Domains\Learning\Enums\LessonProgressStatus;
use App\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class RecordProgressRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(LessonProgressStatus::values())],
            'position_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
