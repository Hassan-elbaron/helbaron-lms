<?php

namespace App\Contexts\Analytics\Enums;

enum AnalyticsPermission: string
{
    case ViewAnalytics = 'analytics.view';
    case ManageReports = 'analytics.reports.manage';
    case ExportAnalytics = 'analytics.export';

    /**
     * Currency-denominated metrics, gated separately from ViewAnalytics so a role can read
     * engagement figures without being handed platform revenue. Instructors hold ViewAnalytics
     * but not this.
     */
    case ViewRevenue = 'analytics.revenue.view';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
