<?php

namespace App\Domains\Catalog\Events;

use App\Domains\Catalog\Models\Course;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseUnpublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Course $course) {}
}
