<?php

namespace App\Platform\Shared\Requests;

use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base FormRequest. Authorization defaults to true (real policies gate at the controller
 * layer once auth exists). On validation failure it emits the ONE standard error envelope
 * with code VALIDATION_ERROR (422), so every domain returns identical validation errors.
 *
 * No business rules — validation wiring only.
 */
abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                code: 'VALIDATION_ERROR',
                message: 'The given data was invalid.',
                details: ['fields' => $validator->errors()->toArray()],
                status: 422,
            )
        );
    }
}
