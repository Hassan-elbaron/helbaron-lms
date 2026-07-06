<?php

namespace App\Domains\Analytics\Export\Writers;

use App\Domains\Analytics\Contracts\ExportWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * XLSX writer via OpenSpout (pure-PHP). Falls back to CSV bytes if the library is unavailable.
 */
class XlsxExportWriter implements ExportWriter
{
    public function write(array $headers, iterable $rows): string
    {
        if (! class_exists(Writer::class)) {
            return (new CsvExportWriter)->write($headers, $rows);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer = new Writer;
        $writer->openToFile($tmp);
        $writer->addRow(Row::fromValues($headers));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_values($row)));
        }
        $writer->close();

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    public function mimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
}
