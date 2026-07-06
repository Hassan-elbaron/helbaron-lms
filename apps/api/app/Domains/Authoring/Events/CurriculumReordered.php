<?php

namespace App\Domains\Authoring\Events;

use App\Domains\Catalog\Models\Course;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurriculumReordered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Course $course) {}
}
