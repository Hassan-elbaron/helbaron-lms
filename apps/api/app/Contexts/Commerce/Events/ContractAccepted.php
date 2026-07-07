<?php

namespace App\Contexts\Commerce\Events;

use App\Contexts\Commerce\Models\Contract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Contract $contract) {}
}
