<?php

namespace App\Domains\Certification\Actions;

use App\Domains\Certification\Enums\CertificateStatus;
use App\Domains\Certification\Events\CertificateIssued;
use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Services\SignatureService;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Storage;

/**
 * Reissues a certificate: clears the stored PDF (so it re-renders), refreshes the signature,
 * and re-activates it if it was revoked. Verification code stays stable.
 */
class ReissueCertificateAction extends BaseAction
{
    public function __construct(private readonly SignatureService $signatures) {}

    public function execute(Certificate $certificate): Certificate
    {
        $certificate = $this->transaction(function () use ($certificate): Certificate {
            if ($certificate->pdf_path !== null) {
                Storage::disk((string) config('certification.pdf.disk', 'local'))->delete($certificate->pdf_path);
            }

            $certificate->forceFill([
                'status' => CertificateStatus::Issued->value,
                'revoked_at' => null,
                'reissued_at' => now(),
                'pdf_path' => null,
                'pdf_generated_at' => null,
            ])->save();

            $certificate->forceFill(['signature_hash' => $this->signatures->hash($certificate)])->save();

            return $certificate;
        });

        CertificateIssued::dispatch($certificate);

        return $certificate;
    }
}
