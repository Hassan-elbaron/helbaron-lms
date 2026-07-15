<?php

namespace App\Platform\Shared\Media\Contracts;

use App\Platform\Shared\Media\Data\MediaAssetRef;

/**
 * Resolves the signable media asset that belongs to a lesson, as a storage-agnostic MediaAssetRef.
 * Owned by the context that holds the media metadata (Authoring). Lets Learning obtain media
 * without importing the LessonMedia Eloquent model. Returns null when the lesson has no media.
 */
interface MediaAssetPort
{
    public function assetForLesson(int $lessonId): ?MediaAssetRef;
}
