<?php

namespace App\Platform\Branding\Adapters;

use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Branding\Data\BrandProfile;
use App\Platform\Shared\Branding\Data\CertificateBrand;
use Throwable;

/**
 * Resolves {@see BrandProfilePort} from the BrandSetting singleton.
 *
 * Read-only by construction: it never creates the settings row, so it is safe on queued mail and
 * certificate rendering. Results are memoised per locale for the lifetime of the process, because a
 * single fan-out can render hundreds of notifications and the brand cannot change mid-request.
 *
 * Resilient by design: if branding cannot be read at all (migrations not yet run during an install,
 * a database blip inside a queued job) it degrades to the env-driven defaults rather than failing
 * the email or the certificate. A brand is presentation, never a reason to lose a delivery.
 */
class BrandProfileAdapter implements BrandProfilePort
{
    /** @var array<string, BrandProfile> */
    private array $memo = [];

    private ?CertificateBrand $certificateMemo = null;

    public function profile(?string $locale = null): BrandProfile
    {
        $locale ??= (string) app()->getLocale();

        return $this->memo[$locale] ??= $this->build($locale);
    }

    private function build(string $locale): BrandProfile
    {
        try {
            $groups = BrandSetting::publicArrayOrDefaults();
        } catch (Throwable) {
            $groups = BrandSetting::defaults();
        }

        $identity = (array) ($groups['identity'] ?? []);
        $email = (array) ($groups['email'] ?? []);
        $logos = (array) ($groups['logos'] ?? []);

        $colors = (array) ($email['colors'] ?? []);
        $emailLogo = trim((string) ($logos['email_logo'] ?? ''));

        return new BrandProfile(
            name: $this->localized($identity['brand_name'] ?? '', $locale),
            companyName: trim((string) ($identity['company_name'] ?? '')),
            supportEmail: trim((string) ($identity['support_email'] ?? '')),
            supportPhone: trim((string) ($identity['support_phone'] ?? '')),
            address: $this->localized($identity['address'] ?? '', $locale),
            copyright: $this->localized($identity['copyright'] ?? '', $locale),
            emailHeader: $this->localized($email['header'] ?? '', $locale),
            emailFooter: $this->localized($email['footer'] ?? '', $locale),
            emailSignature: $this->localized($email['signature'] ?? '', $locale),
            emailColors: [
                'background' => (string) ($colors['background'] ?? '#FFFFFF'),
                'text' => (string) ($colors['text'] ?? '#111827'),
                'button' => (string) ($colors['button'] ?? '#111827'),
            ],
            emailLogoUrl: $emailLogo !== '' ? $emailLogo : null,
            socialLinks: array_filter(
                array_map(
                    static fn ($value): string => is_string($value) ? trim($value) : '',
                    (array) ($email['social_links'] ?? $identity['social_links'] ?? []),
                ),
                static fn (string $value): bool => $value !== '',
            ),
            locale: $locale,
        );
    }

    /**
     * The instance's certificate appearance.
     *
     * Same resilience contract as profile(): read-only, memoised for the process, and degrading to
     * built-in defaults rather than throwing. A branding read that fails must never cost a learner
     * their certificate.
     */
    public function certificate(): CertificateBrand
    {
        return $this->certificateMemo ??= $this->buildCertificate();
    }

    private function buildCertificate(): CertificateBrand
    {
        $fallback = CertificateBrand::fallback();

        try {
            $groups = BrandSetting::publicArrayOrDefaults();
        } catch (Throwable) {
            return $fallback;
        }

        $certificate = (array) ($groups['certificate'] ?? []);
        $colors = (array) ($certificate['colors'] ?? []);
        $margins = (array) ($certificate['margins'] ?? []);

        return new CertificateBrand(
            // publicArrayOrDefaults() has already resolved these through PublicAssetUrlResolver, so
            // they are public URLs or '' — never a raw media reference or storage key.
            backgroundUrl: $this->text($certificate['background'] ?? null),
            logoUrl: $this->text($certificate['logo'] ?? null),
            signatureUrl: $this->text($certificate['signature'] ?? null),
            stampUrl: $this->text($certificate['stamp'] ?? null),
            qrPosition: $this->textOr($certificate['qr_position'] ?? null, $fallback->qrPosition),
            font: $this->textOr($certificate['font'] ?? null, $fallback->font),
            textColor: $this->textOr($colors['text'] ?? null, $fallback->textColor),
            accentColor: $this->textOr($colors['accent'] ?? null, $fallback->accentColor),
            margins: [
                'top' => $this->margin($margins['top'] ?? null, $fallback->margins['top']),
                'right' => $this->margin($margins['right'] ?? null, $fallback->margins['right']),
                'bottom' => $this->margin($margins['bottom'] ?? null, $fallback->margins['bottom']),
                'left' => $this->margin($margins['left'] ?? null, $fallback->margins['left']),
            ],
        );
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** A stored-but-blank value must inherit the default, not blank the certificate. */
    private function textOr(mixed $value, string $fallback): string
    {
        $text = $this->text($value);

        return $text !== '' ? $text : $fallback;
    }

    /** Margins are millimetres; a negative or non-numeric value would break the page box. */
    private function margin(mixed $value, int $fallback): int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : $fallback;
    }

    /**
     * Collapse a possibly-localized branding value for $locale, falling back to the application
     * default locale and then to the first non-empty entry.
     */
    private function localized(mixed $value, string $locale): string
    {
        if (! is_array($value)) {
            return trim((string) $value);
        }

        $fallback = (string) config('shared.default_locale', 'en');

        foreach ([$locale, $fallback] as $candidate) {
            if (filled($value[$candidate] ?? null)) {
                return trim((string) $value[$candidate]);
            }
        }

        foreach ($value as $entry) {
            if (is_string($entry) && filled($entry)) {
                return trim($entry);
            }
        }

        return '';
    }
}
