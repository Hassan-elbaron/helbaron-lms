<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Models\ConsultingRequest;
use App\Shared\Services\BaseService;
use Carbon\CarbonImmutable;

/**
 * Computes SLA due times and breach state for consulting requests.
 */
class ConsultingSlaService extends BaseService
{
    public function dueAt(?CarbonImmutable $from = null): CarbonImmutable
    {
        $from ??= CarbonImmutable::now();

        return $from->addHours((int) config('crm.consulting.sla_hours', 48));
    }

    public function isBreached(ConsultingRequest $request): bool
    {
        return $request->sla_due_at !== null
            && $request->sla_due_at->isPast()
            && ! in_array($request->status->value, ['resolved', 'closed'], true);
    }
}
