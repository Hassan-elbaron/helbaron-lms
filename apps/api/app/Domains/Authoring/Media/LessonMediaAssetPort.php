<?php

namespace App\Domains\Authoring\Media;

use App\Domains\Authoring\Models\LessonMedia;
use App\Platform\Shared\Media\Contracts\MediaAssetPort;
use App\Platform\Shared\Media\Data\MediaAccessPolicy;
use App\Platform\Shared\Media\Data\MediaAssetRef;

/**
 * Authoring's implementation of MediaAssetPort. Authoring owns the LessonMedia metadata, so the
 * lesson -> asset lookup lives here (intra-context). Maps the Eloquent model to a storage-agnostic
 * MediaAssetRef, deliberately excluding the raw Mux asset id (mux_asset_id) so it never leaves the
 * owning context. Mirrors the previous unique $lesson->media relation semantics (null when absent).
 */
class LessonMediaAssetPort implements MediaAssetPort
{
    public function assetForLesson(int $lessonId): ?MediaAssetRef
    {
        $media = LessonMedia::query()->where('lesson_id', $lessonId)->first();

        if ($media === null) {
            return null;
        }

        return new MediaAssetRef(
            id: (string) $media->public_id,
            provider: $media->mux_playback_id !== null ? 'mux' : 's3',
            playbackId: $media->mux_playback_id,
            storageKey: $media->s3_key,
            mimeType: $media->mime_type,
            durationSeconds: $media->duration,
            policy: new MediaAccessPolicy(signed: true, visibility: 'private'),
            metadata: ['filesize' => $media->filesize],
        );
    }
}
