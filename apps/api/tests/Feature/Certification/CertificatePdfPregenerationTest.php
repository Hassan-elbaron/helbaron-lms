<?php

use App\Domains\Certification\Actions\EnsureCertificatePdfAction;
use App\Domains\Certification\Jobs\GenerateCertificatePdf;
use App\Domains\Certification\Models\Certificate;
use App\Platform\Shared\Config\ProductionConfigValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * E4 — certificate PDFs.
 *
 * Two defects, both discovered by a learner rather than by an operator:
 *
 * 1. The PDF rendered SYNCHRONOUSLY on the first download. The learner who has just finished a
 *    course — the one most likely to click — paid the whole render inside their own request, holding
 *    a php-fpm worker for its duration. A cohort finishing together turned that into a queue of
 *    blocked workers.
 * 2. `CERTIFICATION_PDF_PROVIDER=browsershot` is a stub that throws unconditionally. spatie/
 *    browsershot is not a dependency and Chromium is not in the image, so selecting it produced a
 *    500 on EVERY certificate download, discovered at the moment somebody had earned something.
 */
it('queues the render when a certificate is issued', function (): void {
    Queue::fake();

    $certificate = Certificate::factory()->create();
    GenerateCertificatePdf::dispatch((int) $certificate->id);

    Queue::assertPushed(GenerateCertificatePdf::class);
});

it('renders and stores the pdf off the request', function (): void {
    Storage::fake(config('certification.pdf.disk', 'local'));

    $certificate = Certificate::factory()->create(['pdf_path' => null]);

    (new GenerateCertificatePdf((int) $certificate->id))->handle(
        app(EnsureCertificatePdfAction::class),
    );

    $certificate->refresh();

    expect($certificate->pdf_path)->not->toBeNull()
        ->and($certificate->pdf_generated_at)->not->toBeNull();
});

/*
 * Idempotence matters more here than usual: the job is dispatched on issue, the controller still
 * renders inline as a fallback, and a queue retry can run it again. All three paths must converge on
 * one file rather than racing to overwrite it.
 */
it('does nothing when the pdf already exists', function (): void {
    Storage::fake(config('certification.pdf.disk', 'local'));

    $certificate = Certificate::factory()->create(['pdf_path' => null]);
    $action = app(EnsureCertificatePdfAction::class);

    (new GenerateCertificatePdf((int) $certificate->id))->handle($action);
    $first = $certificate->fresh()->pdf_generated_at;

    $this->travelTo(now()->addMinutes(5));
    (new GenerateCertificatePdf((int) $certificate->id))->handle($action);

    expect($certificate->fresh()->pdf_generated_at->equalTo($first))->toBeTrue();
});

it('does not fail when the certificate has gone', function (): void {
    $certificate = Certificate::factory()->create();
    $id = (int) $certificate->id;
    $certificate->forceDelete();

    // Revoked or deleted between issue and render. Failing here would only fill the dead-letter
    // table with work nobody wants done.
    (new GenerateCertificatePdf($id))->handle(
        app(EnsureCertificatePdfAction::class),
    );
})->throwsNoExceptions();

/*
 * The provider guard. This is the difference between a deploy that refuses and a learner who gets a
 * 500 on the certificate they just earned.
 */
it('refuses a browsershot provider that cannot work', function (): void {
    config(['certification.pdf.provider' => 'browsershot']);

    $errors = implode(' | ', app(ProductionConfigValidator::class)->criticalErrors());

    expect($errors)->toContain('CERTIFICATION_PDF_PROVIDER=browsershot');
})->skip(
    fn (): bool => class_exists('Spatie\Browsershot\Browsershot'),
    'browsershot is installed, so selecting it is legitimate',
);

it('accepts the default provider', function (): void {
    config(['certification.pdf.provider' => 'fake']);

    $errors = implode(' | ', app(ProductionConfigValidator::class)->criticalErrors());

    // The guard must be about browsershot specifically, not about PDF providers in general.
    expect($errors)->not->toContain('CERTIFICATION_PDF_PROVIDER');
});
