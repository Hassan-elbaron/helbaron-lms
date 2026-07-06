<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Models\Certificate;
use App\Shared\Services\BaseService;

/**
 * Public verification: resolves a certificate by code and returns only non-sensitive issuance
 * facts (no ids, no storage paths).
 */
class CertificateVerificationService extends BaseService
{
    public function __construct(private readonly SignatureService $signatures) {}

    /** @return array<string, mixed>|null */
    public function verify(string $code): ?array
    {
        $certificate = Certificate::query()
            ->with(['user', 'course'])
            ->where('verification_code', $code)
            ->first();

        if ($certificate === null) {
            return null;
        }

        return [
            'valid' => $certificate->isValid() && $this->signatures->verify($certificate),
            'status' => $certificate->status->value,
            'number' => $certificate->number,
            'holder_name' => $certificate->user?->name,
            'course_title' => $certificate->course?->title,
            'issued_at' => $certificate->issued_at?->toIso8601String(),
            'revoked_at' => $certificate->revoked_at?->toIso8601String(),
        ];
    }
}
