<?php

namespace App\Platform\Notifications\Services;

use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Enums\DeliveryStatus;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Jobs\DeliverNotificationJob;
use App\Platform\Notifications\Models\Notification;
use App\Platform\Notifications\Models\NotificationDelivery;
use App\Platform\Notifications\Models\UserNotificationSetting;
use App\Platform\Shared\Services\BaseService;

/**
 * Creates a notification + per-channel delivery rows (idempotent by dedup key) and queues each
 * delivery. Never sends inline — all delivery is queued.
 */
class NotificationDispatcher extends BaseService
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly PreferenceService $preferences,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, Channel>|null  $channels
     */
    public function dispatchToUserId(int $userId, NotificationCategory $category, string $templateKey, array $data = [], ?array $channels = null, ?string $dedupKey = null): Notification
    {
        $locale = $this->localeForUserId($userId);

        $inApp = $this->renderer->render($templateKey, Channel::InApp, $locale, $data);

        $candidate = $channels ?? $this->defaultChannels();
        $enabled = $this->preferences->enabledChannelsForUserId($userId, $category, $candidate);

        return $this->transaction(function () use ($userId, $category, $templateKey, $data, $locale, $inApp, $enabled, $dedupKey): Notification {
            $notification = Notification::create([
                'user_id' => $userId,
                'category' => $category->value,
                'type' => $templateKey,
                'title' => $inApp->subject,
                'body' => $inApp->body,
                'data' => $data,
                'locale' => $locale,
            ]);

            foreach ($enabled as $channel) {
                $delivery = NotificationDelivery::firstOrCreate(
                    ['notification_id' => $notification->id, 'channel' => $channel->value],
                    [
                        'status' => DeliveryStatus::Pending->value,
                        'dedup_key' => $dedupKey ?? ($templateKey.':'.$userId.':'.$channel->value.':'.now()->format('YmdHi')),
                    ],
                );

                // Queue the delivery (never sent inline).
                DeliverNotificationJob::dispatch($delivery->id)->onQueue((string) config('notifications.queue', 'notifications'));
            }

            return $notification;
        });
    }

    /** @return array<int, Channel> */
    private function defaultChannels(): array
    {
        return array_map(fn (string $c) => Channel::from($c), (array) config('notifications.default_channels', ['in_app']));
    }

    private function localeForUserId(int $userId): string
    {
        $setting = UserNotificationSetting::where('user_id', $userId)->first();

        return $setting?->locale ?? (string) config('notifications.locale.default', 'en');
    }
}
