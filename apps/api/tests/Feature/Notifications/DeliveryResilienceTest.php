<?php

use App\Domains\Notifications\Channels\ChannelManager;
use App\Domains\Notifications\Enums\DeliveryStatus;
use App\Domains\Notifications\Jobs\DeliverNotificationJob;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationDelivery;
use App\Domains\Notifications\Services\RateLimiterService;
use App\Domains\Notifications\Services\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('is idempotent: a non-pending delivery is not re-processed', function () {
    $notification = Notification::factory()->create();
    $delivery = NotificationDelivery::create([
        'notification_id' => $notification->id, 'channel' => 'in_app',
        'status' => DeliveryStatus::Sent->value, 'sent_at' => now(),
    ]);

    (new DeliverNotificationJob($delivery->id))->handle(app(ChannelManager::class), app(TemplateRenderer::class), app(RateLimiterService::class));

    expect($delivery->fresh()->attempts)->toBe(0); // untouched (already sent)
});

it('dead-letters a delivery on terminal failure', function () {
    $notification = Notification::factory()->create();
    $delivery = NotificationDelivery::create([
        'notification_id' => $notification->id, 'channel' => 'in_app', 'status' => DeliveryStatus::Pending->value,
    ]);

    (new DeliverNotificationJob($delivery->id))->failed(new RuntimeException('boom'));

    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Dead)
        ->and($delivery->fresh()->dead_at)->not->toBeNull();
});
