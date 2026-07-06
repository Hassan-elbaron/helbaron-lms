<?php

namespace App\Domains\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued email OTP. Dispatched after the DB commit so the code is never sent for data that
 * was rolled back. In local, MAIL_MAILER=log means nothing is really emailed.
 */
class EmailOtpNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your verification code')
            ->line('Your one-time verification code is:')
            ->line($this->code)
            ->line('It expires shortly. If you did not request it, ignore this email.');
    }
}
