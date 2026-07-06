<?php

namespace App\Domains\Live\Meeting;

use App\Domains\Live\Contracts\MeetingProvider;
use App\Domains\Live\Meeting\Providers\FakeMeetingProvider;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Resolves the meeting provider from config. Only 'fake' is implemented — Zoom/Teams/Meet would
 * be added as separate adapters (no SDKs are referenced here).
 */
class MeetingProviderManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): MeetingProvider
    {
        $provider = (string) config('live.meeting.provider', 'fake');

        return match ($provider) {
            'fake' => $this->app->make(FakeMeetingProvider::class),
            default => throw new RuntimeException("Meeting provider [{$provider}] is not implemented."),
        };
    }
}
