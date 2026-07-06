<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Events\CourseFeaturedToggled;
use App\Domains\Catalog\Models\Course;
use App\Shared\Actions\BaseAction;

class ToggleFeaturedAction extends BaseAction
{
    public function execute(Course $course): Course
    {
        $course = $this->transaction(function () use ($course): Course {
            $course->forceFill(['is_featured' => ! $course->is_featured])->save();

            return $course;
        });

        CourseFeaturedToggled::dispatch($course);

        return $course;
    }
}
