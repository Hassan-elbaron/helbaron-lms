<?php

namespace App\Domains\Assessment\Actions\Assessment;

use App\Domains\Assessment\Enums\AssessmentStatus;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Services\AssessmentPublishGuard;

class SetAssessmentStatusAction
{
    public function __construct(private readonly AssessmentPublishGuard $guard) {}

    public function execute(Assessment $assessment, AssessmentStatus $status): Assessment
    {
        // Only the transition INTO published is guarded. Un-publishing and archiving must always
        // remain possible — an author who spots a broken question needs to be able to pull it
        // immediately, and a guard that blocked that would be actively harmful.
        if ($status === AssessmentStatus::Published) {
            $this->guard->assertPublishable($assessment);
        }

        $assessment->forceFill(['status' => $status->value])->save();

        return $assessment->refresh();
    }
}
