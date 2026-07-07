<?php

namespace App\Contexts\Analytics\Export\Writers;

use App\Contexts\Analytics\Contracts\ExportWriter;

/**
 * CSV writer (no external dependency).
 */
class CsvExportWriter implements ExportWriter
{
    public function write(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function mimeType(): string
    {
        return 'text/csv';
    }
}
