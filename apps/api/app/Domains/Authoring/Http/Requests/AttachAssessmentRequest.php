<?php

namespace App\Domains\Authoring\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `present` + `nullable`: the caller must send the key, and an explicit null is the documented way
 * to detach. Omitting it entirely is a client bug, not a detach request.
 */
class AttachAssessmentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assessment_id' => ['present', 'nullable', 'string', 'max:64'],
        ];
    }
}
