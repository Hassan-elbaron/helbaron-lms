<?php

namespace App\Domains\Catalog\Http\Resources\Instructor;

use App\Platform\Shared\Publishing\Data\ReadinessIssue;
use App\Platform\Shared\Publishing\Data\ReadinessReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a publish-readiness evaluation for the Course Builder panel.
 *
 * `is_publishable` is emitted rather than left for the client to derive from the issue list: the
 * backend is the authority on that verdict, and a client recomputing it could drift.
 *
 * @property ReadinessReport $resource
 */
class ReadinessReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $report = $this->resource;

        return [
            'is_publishable' => $report->isPublishable(),
            'score' => $report->score(),
            'evaluated_at' => $report->evaluatedAt,
            'blockers' => array_map($this->issue(...), $report->blockers()),
            'warnings' => array_map($this->issue(...), $report->warnings()),
            'passed_checks' => $report->passedChecks,
        ];
    }

    /** @return array<string, mixed> */
    private function issue(ReadinessIssue $issue): array
    {
        return [
            'code' => $issue->code,
            'severity' => $issue->severity->value,
            'title' => $issue->title,
            'explanation' => $issue->explanation,
            'recommended_action' => $issue->recommendedAction,
            'entity_type' => $issue->entityType,
            'entity_id' => $issue->entityPublicId,
        ];
    }
}
