<?php

namespace App\Domains\Certification\Listeners;

use App\Contexts\Learning\Events\CourseCompleted;
use App\Domains\Certification\Actions\GenerateCertificateAction;

/**
 * The key integration: when Learning reports a completed course, issue a certificate. Idempotent
 * via GenerateCertificateAction. Certification only reacts to the event — it never polls Learning.
 */
class GenerateCertificateOnCourseCompleted
{
    public function __construct(private readonly GenerateCertificateAction $generate) {}

    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->loadMissing(['course']);

        if ($enrollment->user_id !== null && $enrollment->course !== null) {
            $this->generate->executeByUserId($enrollment->user_id, $enrollment->course, $enrollment->id);
        }
    }
}
