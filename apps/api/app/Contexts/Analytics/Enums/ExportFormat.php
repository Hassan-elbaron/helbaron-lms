<?php

namespace App\Contexts\Analytics\Enums;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
