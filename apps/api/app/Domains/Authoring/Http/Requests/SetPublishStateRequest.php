<?php

namespace App\Domains\Authoring\Http\Requests;

use App\Domains\Authoring\Enums\PublishState;
use App\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class SetPublishStateRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'state' => ['required', Rule::in(PublishState::values())],
        ];
    }
}
