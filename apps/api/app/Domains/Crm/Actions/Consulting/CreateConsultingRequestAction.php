<?php

namespace App\Domains\Crm\Actions\Consulting;

use App\Domains\Crm\Enums\ConsultingRequestStatus;
use App\Domains\Crm\Events\ConsultingRequestCreated;
use App\Domains\Crm\Models\ConsultingRequest;
use App\Domains\Crm\Services\ConsultingSlaService;
use App\Shared\Actions\BaseAction;

class CreateConsultingRequestAction extends BaseAction
{
    public function __construct(private readonly ConsultingSlaService $sla) {}

    /** @param array<string, mixed> $data subject, description, organization_id? */
    public function execute(array $data, int|string|null $requestedBy = null): ConsultingRequest
    {
        $request = $this->transaction(function () use ($data, $requestedBy): ConsultingRequest {
            return ConsultingRequest::create([
                'organization_id' => $data['organization_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'requested_by' => $requestedBy,
                'subject' => $data['subject'],
                'description' => $data['description'] ?? null,
                'status' => ConsultingRequestStatus::New->value,
                'sla_due_at' => $this->sla->dueAt(),
            ]);
        });

        ConsultingRequestCreated::dispatch($request);

        return $request;
    }
}
