<?php

namespace App\Domains\Crm\Events;

use App\Domains\Crm\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadConverted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}
}
