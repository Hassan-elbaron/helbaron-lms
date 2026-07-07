<?php

namespace App\Domains\Catalog\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

/**
 * Validates the public course listing query parameters (all filters keyed by public_id).
 */
class CourseIndexRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string'],
            'level' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }
}
