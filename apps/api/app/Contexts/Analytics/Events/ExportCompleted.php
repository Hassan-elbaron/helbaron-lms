<?php

namespace App\Contexts\Analytics\Events;

use App\Contexts\Analytics\Models\ExportJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ExportJob $job) {}
}
