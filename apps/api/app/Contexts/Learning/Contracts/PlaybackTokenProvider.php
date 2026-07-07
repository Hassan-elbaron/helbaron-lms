<?php

namespace App\Contexts\Learning\Contracts;

use App\Domains\Authoring\Models\LessonMedia;
use App\Contexts\Learning\Playback\Data\PlaybackToken;

/**
 * Produces a signed, expiring media URL from lesson media metadata. Implementations must never
 * leak raw provider identifiers back to callers — only the signed URL. Resolved by config.
 */
interface PlaybackTokenProvider
{
    public function issue(LessonMedia $media, int $ttlSeconds): PlaybackToken;
}
