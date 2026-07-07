<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Events\CourseArchived;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Actions\BaseAction;

class ArchiveCourseAction extends BaseAction
{
    public function execute(Course $course): Course
    {
        $course = $this->transaction(function () use ($course): Course {
            $course->forceFill(['status' => CourseStatus::Archived->value])->save();

            return $course;
        });

        CourseArchived::dispatch($course);

        return $course;
    }
}
