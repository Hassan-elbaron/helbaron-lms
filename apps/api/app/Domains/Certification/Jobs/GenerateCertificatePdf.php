<?php

namespace App\Domains\Certification\Jobs;

use App\Domains\Certification\Actions\EnsureCertificatePdfAction;
use App\Domains\Certification\Models\Certificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Renders a certificate's PDF ahead of the learner asking for it.
 *
 * WHY. `CertificateFileController` called EnsureCertificatePdfAction inline, so the FIRST download of
 * any certificate rendered the PDF synchronously inside the HTTP request. The learner who has just
 * finished a course — the one moment they are most likely to click — is the one who pays for it,
 * and they pay in a request that holds a php-fpm worker for the whole render. A cohort finishing
 * together turns that into a queue of blocked workers.
 *
 * Queued on issue instead, so by the time anyone clicks the file already exists and the download is
 * a signed stream. The inline path REMAINS as a fallback: a queue that is down, a job that failed,
 * or a certificate issued before this existed must still produce a certificate rather than an error.
 * This job removes the cost from the common path; it does not become a new way to fail.
 *
 * Idempotent by delegation — EnsureCertificatePdfAction returns early when the file is already there,
 * so a retry, a replayed event or a race with an inline render costs one existence check.
 */
class GenerateCertificatePdf implements ShouldQueue
{
    use Queueable;

    /** Rendering is deterministic; a repeated failure is a real fault, not bad luck. */
    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(private readonly int $certificateId) {}

    public function handle(EnsureCertificatePdfAction $action): void
    {
        $certificate = Certificate::find($this->certificateId);

        // Revoked, deleted, or superseded between issue and render: nothing to do, and failing the
        // job would only fill the dead-letter table with work nobody wants done.
        if ($certificate === null) {
            return;
        }

        $action->execute($certificate);
    }

    /**
     * A failed pre-render must not look like a failed certificate.
     *
     * The learner can still download — the controller renders inline when the file is absent — so
     * this is an operational signal, not a user-facing failure. It is logged through the queue's
     * standard failure hook (AppServiceProvider::registerQueueFailureLogging) rather than swallowed.
     */
    public function failed(Throwable $e): void
    {
        report($e);
    }
}
