<?php

namespace App\Platform\Shared\Branding\Data;

/**
 * The instance's certificate appearance, resolved and render-ready.
 *
 * `BrandSetting.certificate` — background, logo, signature, stamp, QR position, font, colours and
 * margins — is admin-editable and was read by **zero production code**. An academy could configure
 * its certificate in the branding screen and every certificate it issued would ignore all of it.
 *
 * Lives in Shared for the same reason as {@see BrandProfile}: Certification may depend on Shared but
 * never on the Branding module, so this DTO is what crosses the boundary rather than the
 * BrandSetting model.
 *
 * Every field is presentation-only and safe to render. Image fields are resolved PUBLIC URLs or ''
 * — never a raw media reference or storage key.
 */
final readonly class CertificateBrand
{
    /**
     * @param  array{top:int, right:int, bottom:int, left:int}  $margins
     */
    public function __construct(
        public string $backgroundUrl,
        public string $logoUrl,
        public string $signatureUrl,
        public string $stampUrl,
        public string $qrPosition,
        public string $font,
        public string $textColor,
        public string $accentColor,
        public array $margins,
    ) {}

    /**
     * Built-in defaults, used when branding cannot be read at all.
     *
     * Deliberately image-free: an instance that has configured nothing gets a plain certificate
     * rather than somebody else's logo.
     */
    public static function fallback(): self
    {
        return new self(
            backgroundUrl: '',
            logoUrl: '',
            signatureUrl: '',
            stampUrl: '',
            qrPosition: 'bottom-right',
            font: 'Fraunces',
            textColor: '#21302E',
            accentColor: '#134E4A',
            margins: ['top' => 48, 'right' => 48, 'bottom' => 48, 'left' => 48],
        );
    }
}
