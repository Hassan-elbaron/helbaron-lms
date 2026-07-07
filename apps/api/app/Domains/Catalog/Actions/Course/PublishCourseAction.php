<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Contracts\CoursePublishGuard;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Events\CoursePublished;
use App\Domains\Catalog\Exceptions\CoursePublishBlockedException;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Actions\BaseAction;

class PublishCourseAction extends BaseAction
{
    public function __construct(private readonly CoursePublishGuard $guard) {}

    public function execute(Course $course): Course
    {
        if (! $this->guard->canPublish($course)) {
            throw new CoursePublishBlockedException($this->guard->reason());
        }

        $course = $this->transaction(function () use ($course): Course {
            $course->forceFill([
                'status' => CourseStatus::Published->value,
                'published_at' => $course->published_at ?? now(),
            ])->save();

            return $course;
        });

        CoursePublished::dispatch($course);

        return $course;
    }
}
