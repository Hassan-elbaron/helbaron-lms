<?php

namespace App\Platform\Identity\Notifications;

use App\Platform\Shared\Notifications\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued password-reset notification carrying the reset token. The SPA builds the reset URL;
 * we send the token so the frontend can complete /auth/reset-password.
 *
 * Rendered through {@see BrandedMail} for this instance's academy name, signature and the
 * recipient's locale.
 */
class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token)
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
        // The link must point at the SITE, not the API. This previously read config('app.frontend_url'),
        // a key that does not exist in this application's config — so it always fell through to
        // config('app.url') and mailed every user a reset link on the API host, where there is no
        // reset page. The real key is shared.frontend_url.
        $url = BrandedMail::frontendUrl('reset-password')
            .'?token='.urlencode($this->token)
            .'&email='.urlencode((string) $notifiable->email);

        $arabic = str_starts_with(BrandedMail::locale($notifiable), 'ar');

        $message = BrandedMail::make(
            $arabic ? 'إعادة تعيين كلمة المرور' : 'Reset your password',
            $notifiable,
        );

        return $arabic
            ? $message
                ->line('لقد طلبت إعادة تعيين كلمة المرور.')
                ->action('إعادة تعيين كلمة المرور', $url)
                ->line('إذا لم تطلب ذلك، فلا حاجة لأي إجراء.')
            : $message
                ->line('You requested a password reset.')
                ->action('Reset Password', $url)
                ->line('If you did not request this, no action is needed.');
    }
}
