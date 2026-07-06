<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Domains\Authoring\Enums\LessonType;
use App\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(LessonType::values())],
            'content' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
