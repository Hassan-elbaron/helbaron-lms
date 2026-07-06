<?php

namespace App\Domains\Certification\Listeners;

use App\Domains\Certification\Actions\GenerateCertificateAction;
use App\Domains\Learning\Events\CourseCompleted;

/**
 * The key integration: when Learning reports a completed course, issue a certificate. Idempotent
 * via GenerateCertificateAction. Certification only reacts to the event — it never polls Learning.
 */
class GenerateCertificateOnCourseCompleted
{
    public function __construct(private readonly GenerateCertificateAction $generate) {}

    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->loadMissing(['user', 'course']);

        if ($enrollment->user !== null && $enrollment->course !== null) {
            $this->generate->execute($enrollment->user, $enrollment->course, $enrollment->id);
        }
    }
}
