<?php

namespace App\Domains\Analytics\Events;

use App\Domains\Analytics\Models\ReportRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ReportRun $run) {}
}
