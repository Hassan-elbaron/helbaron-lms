<?php

namespace App\Contexts\Analytics\Export;

use App\Contexts\Analytics\Contracts\ExportWriter;
use App\Contexts\Analytics\Export\Writers\CsvExportWriter;
use App\Contexts\Analytics\Export\Writers\XlsxExportWriter;
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
