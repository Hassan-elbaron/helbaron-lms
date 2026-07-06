<?php

namespace App\Domains\Authoring\Events;

use App\Domains\Authoring\Models\Lesson;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LessonPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Lesson $lesson) {}
}
