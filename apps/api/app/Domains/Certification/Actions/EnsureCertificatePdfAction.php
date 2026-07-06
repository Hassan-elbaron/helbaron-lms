<?php

namespace App\Domains\Certification\Actions;

use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Services\CertificateRenderService;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Storage;

/**
 * Renders and stores the certificate PDF once (idempotent). The stored path is never exposed —
 * downloads are served via a signed stream route.
 */
class EnsureCertificatePdfAction extends BaseAction
{
    public function __construct(private readonly CertificateRenderService $renderer) {}

    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->pdf_path !== null && Storage::disk($this->disk())->exists($certificate->pdf_path)) {
            return $certificate;
        }

        $bytes = $this->renderer->renderBytes($certificate);
        $path = 'certificates/'.$certificate->public_id.'.pdf';

        Storage::disk($this->disk())->put($path, $bytes);

        $certificate->forceFill([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ])->save();

        return $certificate;
    }

    private function disk(): string
    {
        return (string) config('certification.pdf.disk', 'local');
    }
}
