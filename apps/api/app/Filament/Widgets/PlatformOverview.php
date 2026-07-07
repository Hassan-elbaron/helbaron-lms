<?php

namespace App\Filament\Widgets;

use App\Domains\Catalog\Models\Course;
use App\Domains\Commerce\Enums\OrderStatus;
use App\Domains\Commerce\Models\Order;
use App\Domains\Crm\Models\Lead;
use App\Platform\Identity\Models\User;
use App\Domains\Learning\Models\Enrollment;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Notifications\Models\Notification;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Platform KPI overview shown on the admin dashboard. Read-only aggregate counts over existing
 * domain models — no business logic, no secrets, no storage paths.
 */
class PlatformOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Platform overview';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $revenueMinor = (int) Order::query()->where('status', OrderStatus::Paid->value)->sum('total_minor');

        return [
            Stat::make('Users', (string) User::query()->count())
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Courses', (string) Course::query()->count())
                ->description('Catalog courses')
                ->descriptionIcon('heroicon-o-book-open'),

            Stat::make('Orders', (string) Order::query()->count())
                ->description('All orders')
                ->descriptionIcon('heroicon-o-shopping-bag'),

            Stat::make('Revenue', number_format($revenueMinor / 100, 2))
                ->description('Paid orders total')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Enrollments', (string) Enrollment::query()->count())
                ->description('Course enrollments')
                ->descriptionIcon('heroicon-o-academic-cap'),

            Stat::make('Live sessions', (string) LiveSession::query()->count())
                ->description('Scheduled + completed')
                ->descriptionIcon('heroicon-o-video-camera'),

            Stat::make('CRM leads', (string) Lead::query()->count())
                ->description('Pipeline leads')
                ->descriptionIcon('heroicon-o-identification'),

            Stat::make('Notifications', (string) Notification::query()->count())
                ->description('Sent + queued')
                ->descriptionIcon('heroicon-o-bell'),
        ];
    }
}
