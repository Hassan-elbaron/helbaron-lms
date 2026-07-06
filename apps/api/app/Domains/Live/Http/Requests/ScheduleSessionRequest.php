<?php

namespace App\Domains\Live\Http\Requests;

use App\Shared\Requests\BaseFormRequest;

class ScheduleSessionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'timezone' => ['required', 'string', 'max:64'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'waiting_room' => ['nullable', 'boolean'],
            'trainer_ids' => ['nullable', 'array'],
            'trainer_ids.*' => ['integer'],
        ];
    }
}
