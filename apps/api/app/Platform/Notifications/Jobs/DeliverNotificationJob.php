<?php

namespace App\Platform\Notifications\Jobs;

use App\Platform\Notifications\Channels\ChannelManager;
use App\Platform\Notifications\Enums\DeliveryStatus;
use App\Platform\Notifications\Events\NotificationDeadLettered;
use App\Platform\Notifications\Events\NotificationDelivered;
use App\Platform\Notifications\Models\NotificationDelivery;
use App\Platform\Notifications\Services\RateLimiterService;
use App\Platform\Notifications\Services\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Delivers a single NotificationDelivery via its channel. Idempotent (only acts on pending),
 * rate-limited, and retried with backoff; on exhaustion the delivery is dead-lettered.
 */
class DeliverNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $deliveryId) {}

    public function backoff(): array
    {
        return (array) config('notifications.retry.backoff_seconds', [10, 60, 300]);
    }

    public function tries(): int
    {
        return (int) config('notifications.retry.max_attempts', 3);
    }

    public function handle(ChannelManager $channels, TemplateRenderer $renderer, RateLimiterService $limiter): void
    {
        $delivery = NotificationDelivery::with('notification.user')->find($this->deliveryId);

        if ($delivery === null || ! $delivery->isPending()) {
            return; // idempotent: already handled or gone
        }

        if (! $limiter->allow($delivery->notification->user_id)) {
            $this->release(30); // over rate limit — try again shortly

            return;
        }

        $delivery->increment('attempts');

        try {
            $notification = $delivery->notification;
            $rendered = $renderer->render($notification->type, $delivery->channel, $notification->locale, (array) $notification->data);

            $channels->resolve($delivery->channel)->send($delivery, $rendered);

            $delivery->forceFill(['status' => DeliveryStatus::Sent->value, 'sent_at' => now(), 'last_error' => null])->save();
            NotificationDelivered::dispatch($delivery);
        } catch (Throwable $e) {
            $delivery->forceFill(['last_error' => substr($e->getMessage(), 0, 500)])->save();

            if ($delivery->attempts >= $this->tries()) {
                $delivery->forceFill(['status' => DeliveryStatus::Dead->value, 'dead_at' => now()])->save();
                NotificationDeadLettered::dispatch($delivery);

                return; // stop retrying
            }

            throw $e; // retry with backoff
        }
    }

    public function failed(Throwable $e): void
    {
        $delivery = NotificationDelivery::find($this->deliveryId);
        if ($delivery !== null && $delivery->isPending()) {
            $delivery->forceFill(['status' => DeliveryStatus::Dead->value, 'dead_at' => now()])->save();
            NotificationDeadLettered::dispatch($delivery);
        }
    }
}
