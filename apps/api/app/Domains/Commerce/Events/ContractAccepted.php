<?php

namespace App\Domains\Commerce\Events;

use App\Domains\Commerce\Models\Contract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Contract $contract) {}
}
