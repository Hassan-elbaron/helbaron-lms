<?php

namespace App\Domains\Live\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class RecordAttendanceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'left_at' => ['nullable', 'date'],
        ];
    }
}
