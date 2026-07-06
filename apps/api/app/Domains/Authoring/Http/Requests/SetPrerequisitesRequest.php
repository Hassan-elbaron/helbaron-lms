<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

class SetPrerequisitesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'prerequisites' => ['present', 'array'],
            'prerequisites.*' => ['string'],
        ];
    }
}
