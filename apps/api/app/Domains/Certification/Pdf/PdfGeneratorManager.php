<?php

namespace App\Domains\Certification\Pdf;

use App\Domains\Certification\Contracts\PdfGenerator;
use App\Domains\Certification\Pdf\Providers\BrowsershotPdfGenerator;
use App\Domains\Certification\Pdf\Providers\FakePdfGenerator;
use Illuminate\Contracts\Container\Container;

class PdfGeneratorManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): PdfGenerator
    {
        return match ((string) config('certification.pdf.provider', 'fake')) {
            'browsershot' => $this->app->make(BrowsershotPdfGenerator::class),
            default => $this->app->make(FakePdfGenerator::class),
        };
    }
}
