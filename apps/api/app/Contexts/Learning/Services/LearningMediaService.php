<?php

namespace App\Contexts\Learning\Services;

use App\Platform\Shared\Media\Contracts\MediaAssetPort;
use App\Platform\Shared\Media\Contracts\PlaybackPort;
use App\Platform\Shared\Media\Data\PlaybackToken;
use App\Platform\Shared\Media\Exceptions\MediaUnavailableException;
use App\Platform\Shared\Services\BaseService;

/**
 * The ONLY way media is exposed to learners. Verifies access, then returns a signed, expiring
 * PlaybackToken. Media is obtained as a storage-agnostic MediaAssetRef through MediaAssetPort and
 * signed through PlaybackPort — Learning never touches the LessonMedia/Lesson models or raw
 * storage ids. Callers pass the lesson id.
 */
class LearningMediaService extends BaseService
{
    public function __construct(
        private readonly LessonAccessService $access,
        private readonly PlaybackPort $playback,
        private readonly MediaAssetPort $mediaAssets,
    ) {}

    public function playbackForLessonByUserId(int $userId, int $lessonId): PlaybackToken
    {
        $this->access->assertAccessByUserId($userId, $lessonId);

        $asset = $this->mediaAssets->assetForLesson($lessonId);

        if ($asset === null) {
            throw new MediaUnavailableException;
        }

        return $this->playback->issue($asset, (int) config('learning.playback.ttl_seconds', 600));
    }

    public function hasMediaForLesson(int $lessonId): bool
    {
        return $this->mediaAssets->assetForLesson($lessonId) !== null;
    }
}
