<?php

namespace App\Domains\Analytics\Policies;

use App\Domains\Analytics\Models\ReportDefinition;
use App\Domains\Identity\Models\User;
use App\Shared\Policies\BasePolicy;

class ReportDefinitionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof User && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, ReportDefinition $report): bool
    {
        return $report->visibility->value === 'shared'
            || $report->owner_id === $user->id
            || $user->can('analytics.reports.manage');
    }
}
