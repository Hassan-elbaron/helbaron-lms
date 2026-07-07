<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Domains\Authoring\Enums\LessonType;
use App\Platform\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateLessonRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(LessonType::values())],
            'content' => ['nullable', 'array'],
            'is_preview' => ['nullable', 'boolean'],
        ];
    }
}
