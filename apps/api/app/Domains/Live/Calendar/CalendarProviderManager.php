<?php

namespace App\Domains\Live\Calendar;

use App\Domains\Live\Calendar\Providers\NullCalendarProvider;
use App\Domains\Live\Contracts\CalendarProvider;
use Illuminate\Contracts\Container\Container;

class CalendarProviderManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): CalendarProvider
    {
        // Only the Null provider exists; abstraction is ready for future adapters.
        return $this->app->make(NullCalendarProvider::class);
    }
}
