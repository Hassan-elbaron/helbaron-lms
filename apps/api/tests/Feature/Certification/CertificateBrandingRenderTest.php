<?php

use App\Domains\Certification\Services\CertificateVariableRenderer;
use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Branding\Data\CertificateBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * B1 — the certificate must render the academy's own branding.
 *
 * `BrandSetting.certificate` (background, logo, signature, stamp, QR position, font, colours,
 * margins) is admin-editable and was read by **zero production code**: an academy could configure
 * its certificate in the branding screen and every certificate it issued would ignore all of it.
 *
 * Asserted at the RENDERER, not at the settings model — reading the value back out of the model
 * proves only that the database round-trips, which was never in doubt. What was broken is that
 * nothing carried it into the document.
 *
 * Precedence under test throughout: per-template `design` -> instance branding -> built-in default.
 */
function brandCertificateWith(array $certificate): void
{
    $setting = BrandSetting::current();
    $setting->update(['certificate' => $certificate]);

    // The adapter memoises per process, so a fresh one is required after changing the row.
    app()->forgetInstance(BrandProfilePort::class);
    app()->forgetInstance(CertificateVariableRenderer::class);
}

function renderSample(string $html, array $design = []): string
{
    return app(CertificateVariableRenderer::class)->renderSample($html, $design);
}

it('renders the instance logo when the template does not specify one', function (): void {
    brandCertificateWith(['logo' => 'https://cdn.example/acme-logo.png']);

    $html = renderSample('<div>{{ company_logo }}</div>');

    expect($html)->toContain('acme-logo.png');
});

it('lets a template design override the instance logo', function (): void {
    brandCertificateWith(['logo' => 'https://cdn.example/acme-logo.png']);

    // A template built for one programme must not be repainted by an unrelated branding change.
    $html = renderSample('<div>{{ company_logo }}</div>', [
        'company_logo' => 'https://cdn.example/programme-logo.png',
    ]);

    expect($html)->toContain('programme-logo.png')
        ->and($html)->not->toContain('acme-logo.png');
});

it('renders the instance background, signature and stamp', function (): void {
    brandCertificateWith([
        'background' => 'https://cdn.example/acme-bg.png',
        'signature' => 'https://cdn.example/acme-sig.png',
        'stamp' => 'https://cdn.example/acme-stamp.png',
    ]);

    $html = renderSample('{{ background_image }}|{{ signature_image }}|{{ stamp_image }}');

    expect($html)->toContain('acme-bg.png')
        ->and($html)->toContain('acme-sig.png')
        ->and($html)->toContain('acme-stamp.png');
});

it('renders the instance font and colours', function (): void {
    brandCertificateWith([
        'font' => 'Tajawal',
        'colors' => ['text' => '#101010', 'accent' => '#AA0000'],
        'qr_position' => 'bottom-left',
    ]);

    $html = renderSample(
        '<body style="font-family:{{ brand_font }};color:{{ brand_text_color }}">'
        .'<i data-accent="{{ brand_accent_color }}" data-qr="{{ brand_qr_position }}"></i></body>',
    );

    expect($html)->toContain('Tajawal')
        ->toContain('#101010')
        ->toContain('#AA0000')
        ->toContain('bottom-left');
});

/*
 * The negative half. Without it every assertion above would also pass on a renderer that simply
 * echoed whatever it was handed, and the test could not distinguish "branding was consumed" from
 * "the token happened to contain that text".
 */
it('falls back to built-in defaults when the instance has configured nothing', function (): void {
    brandCertificateWith([]);

    $html = renderSample('{{ brand_font }}|{{ brand_text_color }}|{{ brand_qr_position }}|[{{ company_logo }}]');

    expect($html)->toContain('Fraunces')
        ->toContain('#21302E')
        ->toContain('bottom-right')
        // No branding and no template design means NO image, not a broken one and not somebody
        // else's logo.
        ->and($html)->toContain('[]');
});

it('ignores a stored-but-blank branding value rather than blanking the certificate', function (): void {
    brandCertificateWith(['font' => '', 'qr_position' => '', 'colors' => ['text' => '', 'accent' => '']]);

    $html = renderSample('{{ brand_font }}|{{ brand_text_color }}|{{ brand_qr_position }}');

    // Same empty-vs-absent trap as the BRAND_* env keys: a blank stored value must inherit the
    // default, not render an empty font-family and an empty colour.
    expect($html)->toContain('Fraunces')
        ->toContain('#21302E')
        ->toContain('bottom-right');
});

it('carries no vendor string into a certificate for a rebranded instance', function (): void {
    config([
        'branding.name.en' => 'Acme Academy',
        'branding.company_name' => 'Acme Holdings Ltd',
    ]);
    brandCertificateWith(['logo' => 'https://cdn.example/acme-logo.png']);

    $html = renderSample(
        '<div>{{ issuer_name }} {{ company_logo }} {{ brand_font }} {{ holder_name }}</div>',
    );

    expect(strtolower($html))->not->toContain('helbaron')
        ->and($html)->toContain('Acme');
});

it('never lets a branding failure cost a learner their certificate', function (): void {
    // The port degrades to defaults rather than throwing; a certificate must still render.
    $this->mock(BrandProfilePort::class, function ($mock): void {
        $mock->shouldReceive('certificate')
            ->andReturn(CertificateBrand::fallback());
        $mock->shouldReceive('profile')->andReturn(
            app(BrandProfilePort::class)->profile(),
        )->byDefault();
    });

    $html = app(CertificateVariableRenderer::class)
        ->renderSample('{{ holder_name }} {{ brand_font }}');

    expect($html)->toContain('Sample Learner')
        ->toContain('Fraunces');
});
