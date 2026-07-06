<?php

namespace App\Domains\Learning\Services;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Identity\Models\User;
use App\Domains\Learning\Contracts\PlaybackTokenProvider;
use App\Domains\Learning\Exceptions\MediaUnavailableException;
use App\Domains\Learning\Playback\Data\PlaybackToken;
use App\Shared\Services\BaseService;

/**
 * The ONLY way media is exposed to learners. Verifies access, then returns a signed, expiring
 * PlaybackToken. Raw storage identifiers (s3_key / mux_asset_id) never leave this service.
 */
class LearningMediaService extends BaseService
{
    public function __construct(
        private readonly LessonAccessService $access,
        private readonly PlaybackTokenProvider $playback,
    ) {}

    public function playbackFor(User $user, Lesson $lesson): PlaybackToken
    {
        $this->access->assertAccess($user, $lesson);

        $media = $lesson->media;

        if ($media === null) {
            throw new MediaUnavailableException;
        }

        return $this->playback->issue($media, (int) config('learning.playback.ttl_seconds', 600));
    }

    /** True when the lesson has media that can be signed. */
    public function hasMedia(Lesson $lesson): bool
    {
        return $lesson->media !== null;
    }
}
