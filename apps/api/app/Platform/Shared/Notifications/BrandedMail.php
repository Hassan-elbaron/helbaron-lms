<?php

namespace App\Platform\Shared\Notifications;

use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Branding\Data\BrandProfile;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Builds a MailMessage already carrying this instance's brand.
 *
 * Every academy runs its own deployment, so a transactional email that says "HElbaron" — or that
 * silently falls back to APP_NAME while the admin has configured a different brand — is a visible
 * white-label leak in the one channel the customer's own learners read. This centralises the brand
 * lookup, the recipient's locale and the RTL flag so individual notifications never repeat them.
 *
 * Use from any layer: it lives in Shared and depends only on the branding PORT.
 */
final class BrandedMail
{
    /**
     * A MailMessage with the brand's greeting, salutation and subject prefix applied.
     *
     * $subject is the bare subject ("Your verification code"); the brand name is prefixed so the
     * recipient's inbox shows who is writing. Pass the recipient so their preferred locale wins over
     * the process locale — a queued job runs with whatever locale the worker happens to hold.
     */
    public static function make(string $subject, ?object $notifiable = null): MailMessage
    {
        $profile = self::profile($notifiable);
        $name = $profile->name !== '' ? $profile->name : (string) config('app.name');

        $message = (new MailMessage)
            ->subject($name !== '' ? $name.' — '.$subject : $subject)
            ->greeting(self::greeting($profile, $notifiable));

        if ($profile->emailSignature !== '') {
            $message->salutation($profile->emailSignature);
        }

        return $message;
    }

    /**
     * The resolved brand for the recipient's locale.
     */
    public static function profile(?object $notifiable = null): BrandProfile
    {
        return app(BrandProfilePort::class)->profile(self::locale($notifiable));
    }

    /**
     * The recipient's locale, falling back to the application default. Reads the `locale` attribute
     * that User carries; anything else (an on-demand mail route, a plain address) uses the default.
     */
    public static function locale(?object $notifiable = null): string
    {
        $locale = is_object($notifiable) ? ($notifiable->locale ?? null) : null;

        return is_string($locale) && $locale !== ''
            ? $locale
            : (string) config('shared.default_locale', 'en');
    }

    /**
     * The public site URL for links a human clicks.
     *
     * Deliberately NOT config('app.url'): that is the API host. A reset or verification link built
     * from it lands the recipient on a JSON endpoint instead of the site.
     */
    public static function frontendUrl(string $path = ''): string
    {
        $base = rtrim((string) (config('shared.frontend_url') ?: config('app.url')), '/');

        return $path === '' ? $base : $base.'/'.ltrim($path, '/');
    }

    private static function greeting(BrandProfile $profile, ?object $notifiable): string
    {
        $arabic = str_starts_with($profile->locale, 'ar');
        $name = is_object($notifiable) ? trim((string) ($notifiable->name ?? '')) : '';

        if ($name === '') {
            return $arabic ? 'مرحبًا' : 'Hello';
        }

        return $arabic ? 'مرحبًا '.$name : 'Hello '.$name;
    }
}
