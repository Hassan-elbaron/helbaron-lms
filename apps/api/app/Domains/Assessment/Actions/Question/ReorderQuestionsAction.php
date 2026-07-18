<?php

namespace App\Domains\Assessment\Actions\Question;

use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentQuestion;
use Illuminate\Support\Facades\DB;

class ReorderQuestionsAction
{
    /** @param  list<string>  $order  question public_ids in their new order */
    public function execute(Assessment $assessment, array $order): void
    {
        DB::transaction(function () use ($assessment, $order): void {
            foreach ($order as $position => $publicId) {
                // Scoped to THIS assessment: a public_id belonging to another assessment matches
                // nothing and is silently ignored, so a tampered payload cannot reorder or steal
                // questions from an assessment the caller does not own.
                AssessmentQuestion::where('assessment_id', $assessment->id)
                    ->where('public_id', $publicId)
                    ->update(['position' => $position]);
            }
        });
    }
}
