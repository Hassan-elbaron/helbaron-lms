<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Events\CourseUnpublished;
use App\Domains\Catalog\Models\Course;
use App\Shared\Actions\BaseAction;

class UnpublishCourseAction extends BaseAction
{
    public function execute(Course $course): Course
    {
        $course = $this->transaction(function () use ($course): Course {
            $course->forceFill(['status' => CourseStatus::Draft->value])->save();

            return $course;
        });

        CourseUnpublished::dispatch($course);

        return $course;
    }
}
