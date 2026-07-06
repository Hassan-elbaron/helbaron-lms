<?php

namespace App\Domains\Certification\Pdf\Providers;

use App\Domains\Certification\Contracts\PdfGenerator;
use App\Domains\Certification\Pdf\Data\PdfResult;

/**
 * Default generator for local/test. Produces a minimal valid-ish PDF byte stream (no headless
 * browser). Enough to store and stream; not a designed document.
 */
class FakePdfGenerator implements PdfGenerator
{
    public function render(string $html): PdfResult
    {
        $text = trim(strip_tags($html));
        $bytes = "%PDF-1.4\n% HElbaron fake certificate\n".substr($text, 0, 2000)."\n%%EOF";

        return new PdfResult(bytes: $bytes);
    }
}
