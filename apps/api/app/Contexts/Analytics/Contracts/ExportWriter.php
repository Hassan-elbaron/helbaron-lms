<?php

namespace App\Contexts\Analytics\Contracts;

/**
 * Serializes a tabular dataset to file bytes (CSV/XLSX). Storage is the caller's concern.
 */
interface ExportWriter
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function write(array $headers, iterable $rows): string;

    public function extension(): string;

    public function mimeType(): string;
}
