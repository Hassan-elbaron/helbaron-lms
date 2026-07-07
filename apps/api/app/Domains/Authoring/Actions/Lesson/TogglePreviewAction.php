<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Shared\Actions\BaseAction;

class TogglePreviewAction extends BaseAction
{
    public function execute(Lesson $lesson): Lesson
    {
        return $this->transaction(function () use ($lesson): Lesson {
            $lesson->forceFill(['is_preview' => ! $lesson->is_preview])->save();

            return $lesson;
        });
    }
}
