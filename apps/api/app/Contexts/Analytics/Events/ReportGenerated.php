<?php

namespace App\Contexts\Analytics\Events;

use App\Contexts\Analytics\Models\ReportRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ReportRun $run) {}
}
