<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Enums\ActivityType;
use App\Domains\Crm\Models\CrmActivity;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Model;

/**
 * Appends activities to a subject's polymorphic timeline. The activity log IS the CRM audit
 * trail — write it only through here.
 */
class ActivityLogger extends BaseService
{
    public function log(Model $subject, ActivityType $type, ?string $description = null, int|string|null $userId = null): CrmActivity
    {
        return $subject->activities()->create([
            'type' => $type->value,
            'description' => $description,
            'user_id' => $userId,
            'occurred_at' => now(),
        ]);
    }
}
