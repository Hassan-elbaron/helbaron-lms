<?php

namespace App\Domains\Notifications\Providers;

use App\Domains\Notifications\Channels\ProviderManager;
use App\Domains\Notifications\Contracts\Providers\MailProvider;
use App\Domains\Notifications\Contracts\Providers\PushProvider;
use App\Domains\Notifications\Contracts\Providers\SmsProvider;
use App\Domains\Notifications\Listeners\NotificationEventSubscriber;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Policies\NotificationPolicy;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * Wires the Notifications module. Consumer domain: subscribes to producer EVENTS (never their
 * tables) and dispatches queued deliveries via Fake channel/provider abstractions only.
 */
class NotificationsServiceProvider extends BaseDomainServiceProvider
{
    protected array $routeFiles = ['routes/notifications.php'];

    /** @var array<class-string, class-string> */
    protected array $policies = [
        Notification::class => NotificationPolicy::class,
    ];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/notifications.php', 'notifications');

        // Provider selection is config-driven (fake default). Local/test never send for real.
        $this->app->bind(MailProvider::class, fn ($app) => $app->make(ProviderManager::class)->mail());
        $this->app->bind(SmsProvider::class, fn ($app) => $app->make(ProviderManager::class)->sms());
        $this->app->bind(PushProvider::class, fn ($app) => $app->make(ProviderManager::class)->push());
    }

    protected function bootDomain(): void
    {
        Event::subscribe(NotificationEventSubscriber::class);
    }
}
