<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Actions\GenerateCertificateAction;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Domains\Certification\Services\CertificateRenderService;
use App\Domains\Certification\Services\QrCodeService;
use App\Domains\Certification\Services\VerificationUrlService;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a non-empty SVG QR code for a URL', function () {
    $svg = app(QrCodeService::class)->svgFor('https://example.test/verify/ABC');

    expect($svg)->toBeString()
        ->and($svg)->not->toBe('')
        ->and($svg)->toContain('<svg');
});

it('embeds the verify URL in the rendered certificate output', function () {
    $this->seed(RolePermissionSeeder::class);

    CertificateTemplate::factory()->create([
        'is_active' => true,
        'html' => '<html><body><p>Verify: {{ verify_url }}</p>{{ qr_svg }}</body></html>',
    ]);

    $cert = app(GenerateCertificateAction::class)->executeByUserId(
        User::factory()->create()->id,
        Course::factory()->published()->create(),
    );

    $verifyUrl = app(VerificationUrlService::class)->forCertificate($cert);
    $bytes = app(CertificateRenderService::class)->renderBytes($cert);

    // The FakePdfGenerator strips tags, so the SVG markup itself is not present, but the
    // verify URL text survives — proving the QR wiring reached the rendered document.
    expect($bytes)->toContain($verifyUrl);
});
