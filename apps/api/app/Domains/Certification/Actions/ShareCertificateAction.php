<?php

namespace App\Domains\Certification\Actions;

use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Services\VerificationUrlService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Produces the public verification URL for sharing. (No LinkedIn/social integration.)
 */
class ShareCertificateAction extends BaseAction
{
    public function __construct(private readonly VerificationUrlService $urls) {}

    /** @return array{verification_url: string, verification_code: string} */
    public function execute(Certificate $certificate): array
    {
        return [
            'verification_url' => $this->urls->forCertificate($certificate),
            'verification_code' => $certificate->verification_code,
        ];
    }
}
