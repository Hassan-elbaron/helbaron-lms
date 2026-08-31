<?php

namespace App\Platform\Identity\Notifications;

use App\Platform\Shared\Notifications\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued email OTP. Dispatched after the DB commit so the code is never sent for data that
 * was rolled back. In local, MAIL_MAILER=log means nothing is really emailed.
 *
 * Rendered through {@see BrandedMail} so the instance's own academy name, signature and the
 * recipient's locale are applied — this deployment belongs to one customer, and their learners must
 * never see another brand in their inbox.
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
        $arabic = str_starts_with(BrandedMail::locale($notifiable), 'ar');
        $minutes = (int) config('identity.otp.email.ttl_minutes', 10);

        $message = BrandedMail::make(
            $arabic ? 'رمز التحقق الخاص بك' : 'Your verification code',
            $notifiable,
        );

        return $arabic
            ? $message
                ->line('رمز التحقق لمرة واحدة الخاص بك هو:')
                ->line($this->code)
                ->line("تنتهي صلاحيته خلال {$minutes} دقائق. إذا لم تطلبه، تجاهل هذه الرسالة.")
            : $message
                ->line('Your one-time verification code is:')
                ->line($this->code)
                ->line("It expires in {$minutes} minutes. If you did not request it, ignore this email.");
    }
}
