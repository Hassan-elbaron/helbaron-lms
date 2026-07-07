<?php

namespace App\Domains\Learning\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class UpsertNoteRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
