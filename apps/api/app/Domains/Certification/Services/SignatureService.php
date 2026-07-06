<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Models\Certificate;
use App\Shared\Services\BaseService;

/**
 * Computes digital-signature METADATA: a deterministic HMAC over the certificate's identifying
 * fields (not a PKI signature). Lets verification detect tampering of the recorded facts.
 */
class SignatureService extends BaseService
{
    public function hash(Certificate $certificate): string
    {
        $payload = implode('|', [
            $certificate->number,
            $certificate->verification_code,
            $certificate->user_id,
            $certificate->course_id,
            optional($certificate->issued_at)->toIso8601String(),
        ]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function verify(Certificate $certificate): bool
    {
        return $certificate->signature_hash !== null
            && hash_equals($certificate->signature_hash, $this->hash($certificate));
    }
}
