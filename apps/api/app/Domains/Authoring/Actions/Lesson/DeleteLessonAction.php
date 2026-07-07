<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Shared\Actions\BaseAction;

class DeleteLessonAction extends BaseAction
{
    public function execute(Lesson $lesson): void
    {
        $this->transaction(fn () => $lesson->delete());
    }
}
