<?php

namespace App\Domains\Assessment\Http\Requests;

use App\Domains\Assessment\Enums\FeedbackMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for create and update. `sometimes` on every field makes this safe for PATCH-style
 * updates while `required` on title is enforced only on create (see `rules()`).
 *
 * Authorization is NOT done here — the controller calls Gate::authorize so the failure is a 403
 * with a policy behind it rather than a validation 422.
 */
class SaveAssessmentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],

            // 0 is a legitimate pass mark ("participation only"); null means ungraded.
            'passing_score' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'negative_marking' => ['sometimes', 'boolean'],

            // At least one attempt, or null for unlimited. Zero would lock everyone out.
            'max_attempts' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            // Capped at 24h — anything longer is a data-entry error, not a real exam.
            'time_limit_seconds' => ['sometimes', 'nullable', 'integer', 'min:30', 'max:86400'],

            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'questions_per_attempt' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],

            'feedback_mode' => ['sometimes', Rule::in(FeedbackMode::values())],
        ];
    }
}
