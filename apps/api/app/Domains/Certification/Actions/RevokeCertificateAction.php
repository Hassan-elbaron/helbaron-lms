<?php

namespace App\Domains\Certification\Actions;

use App\Domains\Certification\Enums\CertificateStatus;
use App\Domains\Certification\Events\CertificateRevoked;
use App\Domains\Certification\Models\Certificate;
use App\Shared\Actions\BaseAction;

class RevokeCertificateAction extends BaseAction
{
    public function execute(Certificate $certificate): Certificate
    {
        $certificate = $this->transaction(function () use ($certificate): Certificate {
            $certificate->forceFill([
                'status' => CertificateStatus::Revoked->value,
                'revoked_at' => now(),
            ])->save();

            return $certificate;
        });

        CertificateRevoked::dispatch($certificate);

        return $certificate;
    }
}
