<?php

namespace App\Domains\Assessment\Actions\Assessment;

use App\Domains\Assessment\Models\Assessment;

/**
 * Soft-deletes an assessment. Attempts reference it, so a hard delete would erase learners'
 * results — the row must survive for the historical record.
 */
class DeleteAssessmentAction
{
    public function execute(Assessment $assessment): void
    {
        $assessment->delete();
    }
}
