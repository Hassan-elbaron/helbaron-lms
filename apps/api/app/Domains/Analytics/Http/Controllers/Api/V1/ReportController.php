<?php

namespace App\Domains\Analytics\Http\Controllers\Api\V1;

use App\Domains\Analytics\Actions\RunReportAction;
use App\Domains\Analytics\Http\Requests\RunReportRequest;
use App\Domains\Analytics\Http\Resources\ReportDefinitionResource;
use App\Domains\Analytics\Models\ReportDefinition;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportController extends Controller
{
    public function index(): JsonResponse
    {
        $reports = ReportDefinition::query()->latest('id')->get();

        return ApiResponse::success(ReportDefinitionResource::collection($reports));
    }

    public function show(ReportDefinition $report): JsonResponse
    {
        return ApiResponse::success(new ReportDefinitionResource($report));
    }

    public function run(RunReportRequest $request, RunReportAction $action): JsonResponse
    {
        $data = $request->validated();
        $report = ReportDefinition::where('public_id', $data['report'])->first();

        if ($report === null) {
            throw new NotFoundHttpException('Report not found.');
        }

        $run = $action->execute($report, ['from' => $data['from'] ?? null, 'to' => $data['to'] ?? null]);

        return ApiResponse::success([
            'run_id' => $run->public_id,
            'ran_at' => $run->ran_at?->toIso8601String(),
            'result' => $run->result,
        ], 'Report generated.');
    }
}
