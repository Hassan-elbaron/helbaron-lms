<?php

namespace App\Domains\Analytics\Export;

use App\Domains\Analytics\Contracts\ExportWriter;
use App\Domains\Analytics\Export\Writers\CsvExportWriter;
use App\Domains\Analytics\Export\Writers\XlsxExportWriter;
use Illuminate\Contracts\Container\Container;

class ExportWriterManager
{
    public function __construct(private readonly Container $app) {}

    public function for(string $format): ExportWriter
    {
        return match ($format) {
            'xlsx' => $this->app->make(XlsxExportWriter::class),
            default => $this->app->make(CsvExportWriter::class),
        };
    }
}
