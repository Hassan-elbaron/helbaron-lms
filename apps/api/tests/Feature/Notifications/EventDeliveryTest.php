<?php

use App\Platform\Identity\Events\UserRegistered;
use App\Platform\Identity\Models\User;
use App\Domains\Notifications\Enums\DeliveryStatus;
use App\Domains\Notifications\Jobs\DeliverNotificationJob;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues a delivery in response to a producer event (never sent inline)', function () {
    Queue::fake();

    UserRegistered::dispatch(User::factory()->create(['name' => 'Ana']));

    // Notification is created immediately; delivery is QUEUED.
    expect(Notification::where('type', 'welcome')->count())->toBe(1);
    Queue::assertPushed(DeliverNotificationJob::class);
});

it('delivers the in-app notification when the queue processes it', function () {
    NotificationTemplate::factory()->create([
        'key' => 'welcome', 'channel' => 'in_app', 'locale' => 'en',
        'subject' => 'Welcome, {{ name }}', 'body' => 'Hello {{ name }}',
    ]);

    UserRegistered::dispatch(User::factory()->create(['name' => 'Ben'])); // sync queue in tests

    $notification = Notification::where('type', 'welcome')->firstOrFail();
    $delivery = $notification->deliveries()->where('channel', 'in_app')->firstOrFail();

    expect($delivery->status)->toBe(DeliveryStatus::Sent)
        ->and($notification->title)->toBe('Welcome, Ben');
});
