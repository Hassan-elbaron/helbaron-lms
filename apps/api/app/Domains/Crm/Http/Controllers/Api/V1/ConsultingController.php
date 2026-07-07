<?php

namespace App\Domains\Crm\Http\Controllers\Api\V1;

use App\Domains\Crm\Actions\Consulting\CreateConsultingRequestAction;
use App\Domains\Crm\Http\Requests\ConsultingRequestRequest;
use App\Domains\Crm\Http\Resources\ConsultingRequestResource;
use App\Domains\Crm\Models\ConsultingRequest;
use App\Domains\Crm\Models\Organization;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConsultingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = ConsultingRequest::query()
            ->where('requested_by', $request->user()->id)
            ->latest('id')
            ->get();

        return ApiResponse::success(ConsultingRequestResource::collection($requests));
    }

    public function store(ConsultingRequestRequest $request, CreateConsultingRequestAction $action): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['organization'])) {
            $data['organization_id'] = Organization::where('public_id', $data['organization'])->value('id');
        }

        $consulting = $action->execute($data, $request->user()->id);

        return ApiResponse::created(new ConsultingRequestResource($consulting), 'Consulting request submitted.');
    }
}
