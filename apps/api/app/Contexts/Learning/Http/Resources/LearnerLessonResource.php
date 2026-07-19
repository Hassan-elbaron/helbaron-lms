<?php

namespace App\Contexts\Learning\Http\Resources;

use App\Platform\Shared\Curriculum\Data\LessonRef;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Full lesson player payload. Media is exposed ONLY as a signed playback object (url + expiry) —
 * never s3_key or mux_asset_id.
 *
 * Phase 3B: accepts either a Lesson model or a LessonRef in the `lesson` payload slot. Because
 * LessonRef does not carry `content`, the DTO path reads `content` from the payload (`content` key)
 * — the Phase-4 controller supplies it. The model path is unchanged (byte-identical).
 *
 * `assessment` follows the same convention: supplied by the controller, defaulting to null, so the
 * model path and any other caller keep working without change.
 */
class LearnerLessonResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $lesson = $this->resource['lesson'];
        $playback = $this->resource['playback']; // ?PlaybackToken

        if ($lesson instanceof LessonRef) {
            $id = $lesson->publicId;
            $title = $lesson->title;
            $type = $lesson->type;
            $isPreview = $lesson->isPreview;
            $content = $this->resource['content'] ?? null;
        } else {
            $id = $lesson->public_id;
            $title = $lesson->title;
            $type = $lesson->type->value;
            $isPreview = $lesson->is_preview;
            $content = $lesson->content;
        }

        return [
            'id' => $id,
            'title' => $title,
            'type' => $type,
            'content' => $content,
            'is_preview' => $isPreview,
            'playback' => $playback ? [
                'url' => $playback->url,
                'kind' => $playback->kind,
                'expires_at' => $playback->expiresAt->toIso8601String(),
            ] : null,
            'progress' => [
                'status' => $this->resource['progress_status'],
                'position_seconds' => $this->resource['position_seconds'],
            ],
            // Learner-safe assessment reference for quiz lessons; null for every other type and for
            // an unpublished assessment. Publish-gated and field-filtered by the controller, which
            // is where the port lives — this resource only forwards what it was handed.
            'assessment' => $this->resource['assessment'] ?? null,
            'bookmarked' => $this->resource['bookmarked'],
            'note' => $this->resource['note'],
            'navigation' => [
                'previous' => $this->resource['prev'],
                'next' => $this->resource['next'],
            ],
        ];
    }
}
