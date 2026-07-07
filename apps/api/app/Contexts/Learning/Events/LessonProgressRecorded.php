<?php

namespace App\Contexts\Learning\Events;

use App\Domains\Authoring\Models\Lesson;
use App\Contexts\Learning\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LessonProgressRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly Lesson $lesson,
    ) {}
}
