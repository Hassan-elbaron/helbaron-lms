<?php

use App\Domains\Notifications\Channels\Fake\FakeMailProvider;
use App\Domains\Notifications\Channels\ProviderManager;
use App\Domains\Notifications\Channels\Providers\FirebasePushProvider;
use App\Domains\Notifications\Channels\Providers\MailgunMailProvider;
use App\Domains\Notifications\Channels\Providers\TwilioSmsProvider;
use App\Domains\Notifications\Contracts\Providers\MailProvider;
use App\Domains\Notifications\Contracts\Providers\PushProvider;
use App\Domains\Notifications\Contracts\Providers\SmsProvider;
use Illuminate\Support\Facades\Http;

it('sends email through Mailgun when configured', function () {
    config()->set('notifications.providers.mail', 'mailgun');
    config()->set('services.mailgun', ['base_url' => 'https://api.mailgun.net', 'domain' => 'mg.helbaron.test', 'secret' => 'key-x', 'from' => 'no-reply@helbaron.test']);
    Http::fake(['api.mailgun.net/*' => Http::response(['id' => '<1@mg>'])]);

    app(ProviderManager::class)->mail()->send('u@example.com', 'Hi', '<b>Body</b>');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/v3/mg.helbaron.test/messages')
        && $req['to'] === 'u@example.com' && $req['subject'] === 'Hi'
        && $req->hasHeader('Authorization'));
});

it('sends SMS through Twilio when configured', function () {
    config()->set('notifications.providers.sms', 'twilio');
    config()->set('services.twilio', ['base_url' => 'https://api.twilio.com', 'account_sid' => 'AC1', 'auth_token' => 'tok', 'from' => '+15550000000']);
    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'])]);

    app(ProviderManager::class)->sms()->send('+15551112222', 'Code 123');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/2010-04-01/Accounts/AC1/Messages.json')
        && $req['To'] === '+15551112222' && $req['Body'] === 'Code 123');
});

it('sends push through Firebase when configured', function () {
    config()->set('notifications.providers.push', 'firebase');
    config()->set('services.firebase', ['base_url' => 'https://fcm.googleapis.com', 'server_key' => 'srv-key']);
    Http::fake(['fcm.googleapis.com/*' => Http::response(['success' => 1])]);

    app(ProviderManager::class)->push()->send('device-token', 'Title', 'Body');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/fcm/send')
        && $req['to'] === 'device-token'
        && $req->hasHeader('Authorization', 'key=srv-key'));
});

it('resolves fake providers by default and never makes a network call', function () {
    config()->set('notifications.providers.mail', 'fake');
    config()->set('notifications.providers.sms', 'fake');
    config()->set('notifications.providers.push', 'fake');
    Http::fake();

    $manager = app(ProviderManager::class);
    expect($manager->mail())->toBeInstanceOf(FakeMailProvider::class);

    $manager->mail()->send('u@example.com', 'Hi', 'Body');
    $manager->sms()->send('+1', 'x');
    $manager->push()->send('t', 'a', 'b');

    Http::assertNothingSent();
});

it('binds real providers by config through the container', function () {
    config()->set('notifications.providers.mail', 'mailgun');
    config()->set('notifications.providers.sms', 'twilio');
    config()->set('notifications.providers.push', 'firebase');

    expect(app(MailProvider::class))->toBeInstanceOf(MailgunMailProvider::class)
        ->and(app(SmsProvider::class))->toBeInstanceOf(TwilioSmsProvider::class)
        ->and(app(PushProvider::class))->toBeInstanceOf(FirebasePushProvider::class);
});
