<?php

namespace App\Domains\Certification\Pdf\Providers;

use App\Domains\Certification\Contracts\PdfGenerator;
use App\Domains\Certification\Pdf\Data\PdfRenderOptions;
use App\Domains\Certification\Pdf\Data\PdfResult;
use RuntimeException;

/**
 * Real HTML→PDF via Browsershot (headless Chromium). The ONLY class permitted to reference the
 * rendering engine.
 *
 * STILL A STUB — spatie/browsershot is not a dependency and Chromium is not in the image, so this
 * throws. What changed is WHERE that is discovered: ProductionConfigValidator now refuses
 * CERTIFICATION_PDF_PROVIDER=browsershot at deploy time unless the package is actually installed.
 * Selecting it used to be accepted silently and then produced a 500 on every certificate download —
 * found by a learner, at the moment they had earned something.
 *
 * When wired, $options->orientation / pageSize map to Browsershot's landscape()/format(), and the
 * validator's check stops objecting by itself because it tests for the class rather than hardcoding
 * that this provider is broken.
 */
class BrowsershotPdfGenerator implements PdfGenerator
{
    public function render(string $html, PdfRenderOptions $options = new PdfRenderOptions): PdfResult
    {
        throw new RuntimeException('Browsershot PDF rendering is not configured (install spatie/browsershot + Chromium).');
    }
}
