<?php

namespace App\Domains\Assessment\Http\Requests;

use App\Domains\Assessment\Enums\AssessmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetAssessmentStatusRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(AssessmentStatus::values())],
        ];
    }
}
