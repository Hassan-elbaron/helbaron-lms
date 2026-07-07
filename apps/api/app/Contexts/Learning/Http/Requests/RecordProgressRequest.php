<?php

namespace App\Contexts\Learning\Http\Requests;

use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Platform\Shared\Requests\BaseFormRequest;
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
