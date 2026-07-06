<?php

namespace App\Domains\Learning\Providers;

use App\Domains\Learning\Contracts\PlaybackTokenProvider;
use App\Domains\Learning\Events\LessonProgressRecorded;
use App\Domains\Learning\Listeners\UpdateLearningSession;
use App\Domains\Learning\Models\Enrollment;
use App\Domains\Learning\Playback\PlaybackTokenManager;
use App\Domains\Learning\Policies\EnrollmentPolicy;
use App\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * Wires the Learning module: config, migrations, learner routes, the EnrollmentPolicy, the
 * config-driven PlaybackTokenProvider binding, and the progress→session listener.
 */
class LearningServiceProvider extends BaseDomainServiceProvider
{
    protected array $routeFiles = ['routes/learning.php'];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        Enrollment::class => EnrollmentPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/learning.php', 'learning');

        // Media is exposed only via a signed token from the configured provider.
        $this->app->bind(PlaybackTokenProvider::class, fn ($app) => $app->make(PlaybackTokenManager::class)->resolve());
    }

    protected function bootDomain(): void
    {
        Event::listen(LessonProgressRecorded::class, UpdateLearningSession::class);
    }
}
