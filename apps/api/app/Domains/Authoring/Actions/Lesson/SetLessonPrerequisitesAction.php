<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Exceptions\CrossCourseReferenceException;
use App\Domains\Authoring\Exceptions\PrerequisiteCycleException;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Services\CurriculumValidator;
use App\Platform\Shared\Actions\BaseAction;

class SetLessonPrerequisitesAction extends BaseAction
{
    public function __construct(private readonly CurriculumValidator $validator) {}

    /**
     * Replace the lesson's prerequisites. Rejects cross-course references and cycles.
     *
     * @param  array<int, string>  $prerequisitePublicIds
     */
    public function execute(Lesson $lesson, array $prerequisitePublicIds): Lesson
    {
        $ids = Lesson::whereIn('public_id', $prerequisitePublicIds)->pluck('id')->all();

        if (! $this->validator->assertSameCourse($lesson, $ids)) {
            throw new CrossCourseReferenceException;
        }

        if ($this->validator->wouldCreateCycle($lesson->id, $ids)) {
            throw new PrerequisiteCycleException;
        }

        return $this->transaction(function () use ($lesson, $ids): Lesson {
            $lesson->prerequisites()->sync($ids);

            return $lesson->load('prerequisites');
        });
    }
}
