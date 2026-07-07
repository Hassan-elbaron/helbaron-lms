<?php

namespace App\Contexts\Learning\Events;

use App\Contexts\Learning\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Enrollment $enrollment) {}
}
