<?php

namespace App\Platform\Notifications\Channels;

use App\Platform\Notifications\Channels\Fake\FakeEmailChannel;
use App\Platform\Notifications\Channels\Fake\FakePushChannel;
use App\Platform\Notifications\Channels\Fake\FakeSmsChannel;
use App\Platform\Notifications\Contracts\NotificationChannel;
use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Exceptions\ChannelNotSupportedException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the delivery channel implementation. All non-in-app channels wrap a Fake provider.
 */
class ChannelManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(Channel $channel): NotificationChannel
    {
        return match ($channel) {
            Channel::InApp => $this->app->make(InAppChannel::class),
            Channel::Email => $this->app->make(FakeEmailChannel::class),
            Channel::Sms => $this->app->make(FakeSmsChannel::class),
            Channel::Push => $this->app->make(FakePushChannel::class),
            Channel::WhatsApp => $this->app->make(WhatsAppChannel::class),
            default => throw new ChannelNotSupportedException,
        };
    }
}
