<?php

namespace App\Platform\Shared\Branding\Data;

/**
 * A resolved, render-ready snapshot of this instance's brand for one locale.
 *
 * The product ships as a separate deployed instance per customer academy, so every outbound surface
 * (transactional mail, certificates, notification templates) must carry that academy's identity —
 * not the vendor's. This is the provider-neutral shape those surfaces consume, so they never import
 * the Branding model and never learn where the values came from (env defaults, the admin's branding
 * record, or an organization override).
 *
 * Presentation only: no secrets, no credentials. Every field is safe to render publicly.
 */
final readonly class BrandProfile
{
    /**
     * @param  array{background:string, text:string, button:string}  $emailColors
     * @param  array<string, string>  $socialLinks
     */
    public function __construct(
        public string $name,
        public string $companyName,
        public string $supportEmail,
        public string $supportPhone,
        public string $address,
        public string $copyright,
        public string $emailHeader,
        public string $emailFooter,
        public string $emailSignature,
        public array $emailColors,
        public ?string $emailLogoUrl,
        public array $socialLinks,
        public string $locale,
    ) {}

    /**
     * The variables exposed to notification templates as {{ brand_* }} placeholders.
     *
     * @return array<string, string>
     */
    public function templateVariables(): array
    {
        return [
            'brand' => $this->name,
            'brand_name' => $this->name,
            'brand_company' => $this->companyName,
            'brand_support_email' => $this->supportEmail,
            'brand_support_phone' => $this->supportPhone,
            'brand_address' => $this->address,
            'brand_signature' => $this->emailSignature,
            'brand_footer' => $this->emailFooter,
        ];
    }
}
