<?php

namespace App\Domains\Crm\Enums;

enum CrmPermission: string
{
    case ManageOrganizations = 'crm.organizations.manage';
    case ManageLeads = 'crm.leads.manage';
    case ManageConsulting = 'crm.consulting.manage';
    case ViewCrm = 'crm.view';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
