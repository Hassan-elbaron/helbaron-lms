<?php

namespace App\Domains\Crm\Actions\Organization;

use App\Domains\Crm\Enums\MemberStatus;
use App\Domains\Crm\Models\OrganizationMember;
use App\Shared\Actions\BaseAction;

class RemoveMemberAction extends BaseAction
{
    public function execute(OrganizationMember $member): void
    {
        $this->transaction(function () use ($member): void {
            $member->forceFill(['status' => MemberStatus::Removed->value])->save();
        });
    }
}
