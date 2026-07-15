<?php

namespace App\Platform\Media\Providers;

use App\Platform\Media\Playback\PlaybackTokenManager;
use App\Platform\Shared\Media\Contracts\PlaybackPort;
use Illuminate\Support\ServiceProvider;

/**
 * Media platform wiring. Binds the config-resolved PlaybackPort (fake | s3 | cloudfront | mux).
 * Signing was relocated here from Learning; configuration keys are unchanged.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlaybackPort::class, fn ($app) => $app->make(PlaybackTokenManager::class)->resolve());
    }
}
