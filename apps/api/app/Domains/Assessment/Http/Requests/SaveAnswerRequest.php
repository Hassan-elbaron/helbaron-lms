<?php

namespace App\Domains\Assessment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The response envelope is type-dependent, so it is validated structurally rather than by shape:
 * the graders are all written to tolerate a missing or malformed payload and score it as
 * incorrect. Over-validating here would mean re-implementing every question type's contract in a
 * second place — exactly the branching the domain avoids.
 *
 * The caps are abuse limits, not domain rules.
 */
class SaveAnswerRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'string', 'max:64'],

            'response' => ['present', 'nullable', 'array'],

            'response.option_ids' => ['sometimes', 'array', 'max:100'],
            'response.option_ids.*' => ['string', 'max:64'],

            'response.text' => ['sometimes', 'nullable', 'string', 'max:10000'],

            'response.blanks' => ['sometimes', 'array', 'max:100'],
            'response.blanks.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
