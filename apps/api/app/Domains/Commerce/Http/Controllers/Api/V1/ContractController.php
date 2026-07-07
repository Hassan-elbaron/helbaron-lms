<?php

namespace App\Domains\Commerce\Http\Controllers\Api\V1;

use App\Domains\Commerce\Actions\Contract\AcceptContractAction;
use App\Domains\Commerce\Http\Resources\ContractResource;
use App\Domains\Commerce\Models\Contract;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contracts = Contract::query()
            ->where('user_id', $request->user()->id)
            ->with(['template', 'order'])
            ->latest('id')
            ->get();

        return ApiResponse::success(ContractResource::collection($contracts));
    }

    public function accept(Request $request, Contract $contract, AcceptContractAction $action): JsonResponse
    {
        Gate::authorize('accept', $contract);

        $contract = $action->execute($contract, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return ApiResponse::updated(new ContractResource($contract->load(['template', 'order'])), 'Contract accepted.');
    }
}
