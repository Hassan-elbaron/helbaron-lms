<?php

namespace App\Domains\Analytics\Events;

use App\Domains\Analytics\Models\ExportJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ExportJob $job) {}
}
