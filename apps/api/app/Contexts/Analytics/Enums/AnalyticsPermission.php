<?php

namespace App\Contexts\Analytics\Enums;

enum AnalyticsPermission: string
{
    case ViewAnalytics = 'analytics.view';
    case ManageReports = 'analytics.reports.manage';
    case ExportAnalytics = 'analytics.export';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
