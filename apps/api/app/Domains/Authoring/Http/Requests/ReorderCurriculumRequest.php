<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

/**
 * Full drag-and-drop tree: ordered sections, each with an ordered list of lesson public_ids.
 */
class ReorderCurriculumRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tree' => ['required', 'array', 'min:1'],
            'tree.*.id' => ['required', 'string'],
            'tree.*.lessons' => ['sometimes', 'array'],
            'tree.*.lessons.*' => ['string'],
        ];
    }
}
