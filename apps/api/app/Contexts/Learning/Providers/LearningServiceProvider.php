<?php

namespace App\Contexts\Learning\Providers;

use App\Contexts\Learning\Events\LessonProgressRecorded;
use App\Contexts\Learning\Listeners\UpdateLearningSession;
use App\Contexts\Learning\Models\Enrollment;
use App\Contexts\Learning\Policies\EnrollmentPolicy;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * Wires the Learning module: config, migrations, learner routes, the EnrollmentPolicy, and the
 * progress→session listener. Media signing is provided by the Media platform (PlaybackPort) and
 * the lesson→asset lookup by Authoring (MediaAssetPort); Learning only consumes those ports.
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
    }

    protected function bootDomain(): void
    {
        Event::listen(LessonProgressRecorded::class, UpdateLearningSession::class);
    }
}
