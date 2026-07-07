<?php

namespace App\Platform\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Queued phone OTP. No live SMS channel is wired at this stage (never send real SMS), so
 * via() is empty — a real SMS provider is added in a later step. The code is still carried
 * so tests can assert it via Notification::fake().
 */
class PhoneOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $code)
    {
        $this->afterCommit = true;
        $this->onQueue('notifications');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [];
    }
}
