<?php

namespace App\Domains\Learning\Http\Resources;

use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Full lesson player payload. Media is exposed ONLY as a signed playback object (url +
 * expiry) — never s3_key or mux_asset_id.
 */
class LearnerLessonResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $lesson = $this->resource['lesson'];
        $playback = $this->resource['playback']; // ?PlaybackToken

        return [
            'id' => $lesson->public_id,
            'title' => $lesson->title,
            'type' => $lesson->type->value,
            'content' => $lesson->content,
            'is_preview' => $lesson->is_preview,
            'playback' => $playback ? [
                'url' => $playback->url,
                'kind' => $playback->kind,
                'expires_at' => $playback->expiresAt->toIso8601String(),
            ] : null,
            'progress' => [
                'status' => $this->resource['progress_status'],
                'position_seconds' => $this->resource['position_seconds'],
            ],
            'bookmarked' => $this->resource['bookmarked'],
            'note' => $this->resource['note'],
            'navigation' => [
                'previous' => $this->resource['prev'],
                'next' => $this->resource['next'],
            ],
        ];
    }
}
