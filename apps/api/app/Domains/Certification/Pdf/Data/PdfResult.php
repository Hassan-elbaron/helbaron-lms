<?php

namespace App\Domains\Certification\Pdf\Data;

/**
 * The rendered PDF as raw bytes. The Certification service decides where/how to store it — the
 * generator never touches storage paths.
 */
final readonly class PdfResult
{
    public function __construct(
        public string $bytes,
        public string $mimeType = 'application/pdf',
    ) {}
}
