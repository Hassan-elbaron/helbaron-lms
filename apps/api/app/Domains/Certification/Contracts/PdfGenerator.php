<?php

namespace App\Domains\Certification\Contracts;

use App\Domains\Certification\Pdf\Data\PdfResult;

/**
 * Renders HTML into PDF bytes. Only concrete adapters reference a rendering engine; domain code
 * depends on this contract. Resolved by config (fake | browsershot).
 */
interface PdfGenerator
{
    public function render(string $html): PdfResult;
}
