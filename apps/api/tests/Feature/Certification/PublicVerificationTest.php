<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Actions\GenerateCertificateAction;
use App\Domains\Certification\Actions\RevokeCertificateAction;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('verifies a certificate publicly without auth and leaks no storage path', function () {
    CertificateTemplate::factory()->create(['is_active' => true]);
    $user = User::factory()->create(['name' => 'Nour Hassan']);
    $course = Course::factory()->published()->create(['title' => 'Laravel Mastery']);

    $cert = app(GenerateCertificateAction::class)->execute($user, $course);

    $res = $this->getJson("/api/v1/certificates/verify/{$cert->verification_code}")->assertOk();

    expect($res->json('data.valid'))->toBeTrue()
        ->and($res->json('data.holder_name'))->toBe('Nour Hassan')
        ->and($res->json('data.course_title'))->toBe('Laravel Mastery');

    expect($res->getContent())->not->toContain('pdf_path')->not->toContain('.pdf');
});

it('returns invalid for a revoked certificate and 404 for unknown codes', function () {
    CertificateTemplate::factory()->create(['is_active' => true]);
    $cert = app(GenerateCertificateAction::class)->execute(User::factory()->create(), Course::factory()->published()->create());
    app(RevokeCertificateAction::class)->execute($cert);

    $this->getJson("/api/v1/certificates/verify/{$cert->verification_code}")
        ->assertOk()->assertJsonPath('data.valid', false)->assertJsonPath('data.status', 'revoked');

    $this->getJson('/api/v1/certificates/verify/UNKNOWNCODE')->assertStatus(404);
});
