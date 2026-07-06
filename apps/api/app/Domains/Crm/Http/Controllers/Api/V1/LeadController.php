<?php

namespace App\Domains\Crm\Http\Controllers\Api\V1;

use App\Domains\Crm\Actions\Lead\CreateLeadAction;
use App\Domains\Crm\Http\Requests\CreateLeadRequest;
use App\Domains\Crm\Http\Resources\LeadResource;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Services\CrmSearchService;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class LeadController extends Controller
{
    public function index(Request $request, CrmSearchService $search): JsonResponse
    {
        Gate::authorize('viewAny', Lead::class);

        $leads = $search->leads($request->only(['q', 'status']), (int) $request->input('per_page', 15));

        return ApiResponse::paginated($leads, LeadResource::class);
    }

    public function store(CreateLeadRequest $request, CreateLeadAction $action): JsonResponse
    {
        Gate::authorize('create', Lead::class);

        $lead = $action->execute($request->validated(), $request->user()->id);

        return ApiResponse::created(new LeadResource($lead->load('stage')), 'Lead created.');
    }
}
