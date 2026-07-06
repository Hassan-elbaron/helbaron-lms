<?php

namespace App\Domains\Analytics\Http\Controllers\Api\V1;

use App\Domains\Analytics\Actions\CreateExportJobAction;
use App\Domains\Analytics\Http\Requests\CreateExportRequest;
use App\Domains\Analytics\Http\Resources\ExportJobResource;
use App\Domains\Analytics\Models\ExportJob;
use App\Domains\Analytics\Models\ReportDefinition;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExportController extends Controller
{
    public function store(CreateExportRequest $request, CreateExportJobAction $action): JsonResponse
    {
        $data = $request->validated();
        $report = ReportDefinition::where('public_id', $data['report'])->first();
        if ($report === null) {
            throw new NotFoundHttpException('Report not found.');
        }

        $job = $action->execute($request->user(), $data['format'], 'report', [
            'report_definition_id' => $report->id,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ]);

        return ApiResponse::created(new ExportJobResource($job), 'Export queued.');
    }

    public function show(Request $request, ExportJob $export): JsonResponse
    {
        Gate::authorize('view', $export);

        $download = null;
        if ($export->isCompleted()) {
            $download = URL::temporarySignedRoute(
                'analytics.exports.file',
                now()->addMinutes((int) config('analytics.export.download_ttl_minutes', 15)),
                ['export' => $export->public_id],
            );
        }

        return ApiResponse::success([
            'export' => (new ExportJobResource($export))->resolve(),
            'download_url' => $download,
        ]);
    }

    public function file(string $export): mixed
    {
        $job = ExportJob::where('public_id', $export)->first();
        if ($job === null || ! $job->isCompleted()) {
            throw new NotFoundHttpException('Export not available.');
        }

        $disk = Storage::disk((string) config('analytics.export.disk', 'local'));

        return response($disk->get($job->file_path), 200, [
            'Content-Type' => $job->format->value === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv',
            'Content-Disposition' => 'attachment; filename="export-'.$job->public_id.'.'.$job->format->value.'"',
        ]);
    }
}
