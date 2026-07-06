<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Models\Certificate;
use App\Shared\Services\BaseService;

/**
 * Builds the public verification URL for a certificate (frontend renders the result).
 */
class VerificationUrlService extends BaseService
{
    public function forCode(string $code): string
    {
        $base = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/');
        $path = trim((string) config('certification.verification.path', 'certificates/verify'), '/');

        return "{$base}/{$path}/{$code}";
    }

    public function forCertificate(Certificate $certificate): string
    {
        return $this->forCode($certificate->verification_code);
    }
}
