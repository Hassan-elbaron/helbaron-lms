<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

/**
 * Ordered list of public_ids (sections or lessons).
 */
class ReorderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'string'],
        ];
    }
}
