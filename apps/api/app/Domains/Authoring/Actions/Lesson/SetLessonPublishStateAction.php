<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Events\LessonPublished;
use App\Domains\Authoring\Models\Lesson;
use App\Platform\Shared\Actions\BaseAction;

class SetLessonPublishStateAction extends BaseAction
{
    public function execute(Lesson $lesson, PublishState $state): Lesson
    {
        $wasPublished = $lesson->isPublished();

        $lesson = $this->transaction(function () use ($lesson, $state): Lesson {
            $lesson->forceFill(['publish_state' => $state->value])->save();

            return $lesson;
        });

        if (! $wasPublished && $state->isPublished()) {
            LessonPublished::dispatch($lesson);
        }

        return $lesson;
    }
}
