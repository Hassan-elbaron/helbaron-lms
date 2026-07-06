<?php

namespace App\Domains\Crm\Events;

use App\Domains\Crm\Models\ConsultingRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultingRequestCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ConsultingRequest $request) {}
}
