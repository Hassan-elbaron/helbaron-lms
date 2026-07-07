<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Contracts\PdfGenerator;
use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Models\CertificateSetting;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Platform\Shared\Services\BaseService;

/**
 * Builds certificate HTML from the template + data, then renders PDF bytes via the PdfGenerator
 * abstraction. No storage concerns here.
 */
class CertificateRenderService extends BaseService
{
    public function __construct(
        private readonly PdfGenerator $pdf,
        private readonly QrCodeService $qr,
        private readonly VerificationUrlService $urls,
    ) {}

    public function renderBytes(Certificate $certificate): string
    {
        $certificate->loadMissing(['user', 'course', 'template']);
        $template = $certificate->template ?? $this->fallbackTemplate();

        $html = $this->fill($template->html, $certificate);

        return $this->pdf->render($html)->bytes;
    }

    private function fill(string $html, Certificate $certificate): string
    {
        $settings = CertificateSetting::current();
        $verifyUrl = $this->urls->forCertificate($certificate);

        $replacements = [
            '{{ holder_name }}' => (string) $certificate->user?->name,
            '{{ course_title }}' => (string) $certificate->course?->title,
            '{{ number }}' => $certificate->number,
            '{{ verification_code }}' => $certificate->verification_code,
            '{{ verify_url }}' => $verifyUrl,
            '{{ issuer_name }}' => $settings->issuer_name,
            '{{ signature_name }}' => (string) $certificate->signature_name,
            '{{ signature_title }}' => (string) $certificate->signature_title,
            '{{ issued_at }}' => optional($certificate->issued_at)->toFormattedDateString(),
            '{{ qr_svg }}' => $this->qr->svgFor($verifyUrl),
        ];

        return strtr($html, $replacements);
    }

    private function fallbackTemplate(): CertificateTemplate
    {
        return CertificateTemplate::where('is_active', true)->orderByDesc('version')->firstOrNew([
            'html' => '<html><body>{{ holder_name }} — {{ course_title }} — {{ number }}</body></html>',
        ]);
    }
}
