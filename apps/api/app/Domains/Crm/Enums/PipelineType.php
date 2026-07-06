<?php

namespace App\Domains\Crm\Enums;

enum PipelineType: string
{
    case Sales = 'sales';
    case Consulting = 'consulting';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
