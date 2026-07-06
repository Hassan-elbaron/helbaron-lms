<?php

namespace App\Domains\Crm\Events;

use App\Domains\Crm\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Organization $organization) {}
}
