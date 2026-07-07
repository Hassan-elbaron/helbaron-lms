<?php

namespace App\Domains\Crm\Http\Requests;

use App\Domains\Crm\Enums\MemberRole;
use App\Platform\Shared\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', Rule::in(MemberRole::values())],
        ];
    }
}
