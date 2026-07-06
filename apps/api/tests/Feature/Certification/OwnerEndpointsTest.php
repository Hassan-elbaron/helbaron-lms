<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Actions\GenerateCertificateAction;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists my certificates, returns a signed download url, and a share url', function () {
    CertificateTemplate::factory()->create(['is_active' => true]);
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $cert = app(GenerateCertificateAction::class)->execute($user, $course);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/my-certificates')->assertOk()->assertJsonPath('data.0.number', $cert->number);

    $download = $this->postJson("/api/v1/certificates/{$cert->public_id}/download")->assertOk();
    expect($download->json('data.download_url'))->toContain('signature=')->toContain('/file');
    expect($download->getContent())->not->toContain('pdf_path');

    $share = $this->postJson("/api/v1/certificates/{$cert->public_id}/share")->assertOk();
    expect($share->json('data.verification_code'))->toBe($cert->verification_code);
});

it('forbids viewing another user certificate', function () {
    CertificateTemplate::factory()->create(['is_active' => true]);
    $owner = User::factory()->create();
    $cert = app(GenerateCertificateAction::class)->execute($owner, Course::factory()->published()->create());

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/certificates/{$cert->public_id}")->assertStatus(403);
});
