<?php

namespace App\Domains\Assessment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1', 'max:500'],
            'order.*' => ['required', 'string', 'max:64'],
        ];
    }
}
