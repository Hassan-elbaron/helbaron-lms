<?php

namespace App\Domains\Commerce\Http\Requests;

use App\Platform\Shared\Requests\BaseFormRequest;

class AddToCartRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'product' => ['required', 'string'],       // product public_id
            'coupon_code' => ['nullable', 'string'],
        ];
    }
}
