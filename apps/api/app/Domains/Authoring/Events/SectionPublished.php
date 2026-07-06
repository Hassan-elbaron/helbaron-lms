<?php

namespace App\Domains\Authoring\Events;

use App\Domains\Authoring\Models\Section;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SectionPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Section $section) {}
}
