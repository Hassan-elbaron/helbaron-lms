<?php

namespace App\Domains\Crm\Events;

use App\Domains\Crm\Models\OrganizationMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberInvited
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly OrganizationMember $member) {}
}
