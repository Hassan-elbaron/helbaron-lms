<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Channels\Fake\FakeMailProvider;
use App\Domains\Notifications\Channels\Fake\FakePushProvider;
use App\Domains\Notifications\Channels\Fake\FakeSmsProvider;
use App\Domains\Notifications\Channels\Providers\FirebasePushProvider;
use App\Domains\Notifications\Channels\Providers\MailgunMailProvider;
use App\Domains\Notifications\Channels\Providers\TwilioSmsProvider;
use App\Domains\Notifications\Contracts\Providers\MailProvider;
use App\Domains\Notifications\Contracts\Providers\PushProvider;
use App\Domains\Notifications\Contracts\Providers\SmsProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpClient;

/**
 * Resolves real vs fake Mail/SMS/Push providers from config/notifications.php. Defaults to Fake
 * so local/test never send. Vendor config is injected here — adapters read no globals.
 */
class ProviderManager
{
    public function __construct(private readonly Container $app) {}

    public function mail(): MailProvider
    {
        return match ((string) config('notifications.providers.mail', 'fake')) {
            'mailgun' => new MailgunMailProvider($this->http(), (array) config('services.mailgun')),
            default => $this->app->make(FakeMailProvider::class),
        };
    }

    public function sms(): SmsProvider
    {
        return match ((string) config('notifications.providers.sms', 'fake')) {
            'twilio' => new TwilioSmsProvider($this->http(), (array) config('services.twilio')),
            default => $this->app->make(FakeSmsProvider::class),
        };
    }

    public function push(): PushProvider
    {
        return match ((string) config('notifications.providers.push', 'fake')) {
            'firebase' => new FirebasePushProvider($this->http(), (array) config('services.firebase')),
            default => $this->app->make(FakePushProvider::class),
        };
    }

    private function http(): HttpClient
    {
        return $this->app->make(HttpClient::class);
    }
}
