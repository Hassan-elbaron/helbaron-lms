<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Models\Certificate;
use App\Platform\Shared\Services\BaseService;

/**
 * Builds the public verification URL for a certificate (frontend renders the result).
 */
class VerificationUrlService extends BaseService
{
    public function forCode(string $code): string
    {
        // shared.frontend_url, not app.frontend_url: the latter is not a key in this application's
        // config, so this silently fell back to the API host and printed a QR/verification link on
        // every certificate that pointed at an endpoint with no verification page.
        $base = rtrim((string) (config('shared.frontend_url') ?: config('app.url')), '/');
        $path = trim((string) config('certification.verification.path', 'certificates/verify'), '/');

        return "{$base}/{$path}/{$code}";
    }

    public function forCertificate(Certificate $certificate): string
    {
        return $this->forCode($certificate->verification_code);
    }
}
