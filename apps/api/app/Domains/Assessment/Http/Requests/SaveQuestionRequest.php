<?php

namespace App\Domains\Assessment\Http\Requests;

use App\Domains\Assessment\Enums\Difficulty;
use App\Domains\Assessment\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Field-level validation only. Whether the option set actually makes sense for the question type
 * ("a single-choice question with two correct answers") is decided by QuestionShapeGuard in the
 * action — that rule is domain meaning, not request shape, and must hold for every caller.
 */
class SaveQuestionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'type' => [$creating ? 'required' : 'sometimes', Rule::in(QuestionType::values())],
            'prompt' => [$creating ? 'required' : 'sometimes', 'string', 'max:10000'],

            'config' => ['sometimes', 'nullable', 'array'],
            'explanation' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'hint' => ['sometimes', 'nullable', 'string', 'max:2000'],

            // Decimal to support half marks; a question must be worth something to be gradable.
            'points' => ['sometimes', 'numeric', 'min:0.01', 'max:9999'],
            // Stored as a positive magnitude; the scorer applies the sign.
            'negative_points' => ['sometimes', 'numeric', 'min:0', 'max:9999'],

            'difficulty' => ['sometimes', 'nullable', Rule::in(Difficulty::values())],

            'options' => [$creating ? 'required' : 'sometimes', 'array', 'max:100'],
            'options.*.label' => ['nullable', 'string', 'max:2000'],
            'options.*.value' => ['nullable', 'string', 'max:512'],
            'options.*.is_correct' => ['sometimes', 'boolean'],
            'options.*.group_index' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'options.*.feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
