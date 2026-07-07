<?php

use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Models\NotificationTemplate;
use App\Platform\Notifications\Services\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a localized template and substitutes placeholders', function () {
    NotificationTemplate::factory()->create(['key' => 'welcome', 'channel' => 'in_app', 'locale' => 'ar', 'subject' => 'مرحبا {{ name }}', 'body' => 'أهلا {{ name }}']);

    $msg = app(TemplateRenderer::class)->render('welcome', Channel::InApp, 'ar', ['name' => 'سارة']);

    expect($msg->locale)->toBe('ar')
        ->and($msg->subject)->toBe('مرحبا سارة')
        ->and($msg->body)->toBe('أهلا سارة');
});

it('falls back to the fallback locale then a generic message', function () {
    NotificationTemplate::factory()->create(['key' => 'welcome', 'channel' => 'in_app', 'locale' => 'en', 'subject' => 'Hi {{ name }}', 'body' => 'Hello {{ name }}']);

    // ar missing -> falls back to en
    $fallback = app(TemplateRenderer::class)->render('welcome', Channel::InApp, 'ar', ['name' => 'Sam']);
    expect($fallback->subject)->toBe('Hi Sam');

    // unknown key -> generic
    $generic = app(TemplateRenderer::class)->render('unknown_key', Channel::InApp, 'en', ['title' => 'T', 'body' => 'B']);
    expect($generic->subject)->toBe('T')->and($generic->body)->toBe('B');
});
