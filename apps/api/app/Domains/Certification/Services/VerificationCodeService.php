<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Models\Certificate;
use App\Shared\Services\BaseService;
use Illuminate\Support\Str;

/**
 * Generates a collision-checked public verification code.
 */
class VerificationCodeService extends BaseService
{
    public function generate(): string
    {
        do {
            $code = Str::upper(Str::random(16));
        } while (Certificate::where('verification_code', $code)->withTrashed()->exists());

        return $code;
    }
}
