<?php

namespace App\Platform\Shared\Media\Contracts;

use App\Platform\Shared\Media\Data\MediaAssetRef;
use App\Platform\Shared\Media\Data\PlaybackToken;
use App\Platform\Shared\Media\Exceptions\MediaUnavailableException;

/**
 * Produces a signed, expiring media URL from an asset reference. Implementations must never leak
 * raw provider identifiers back to callers — only the signed URL. Resolved by config in the Media
 * platform. Replaces the former Learning\Contracts\PlaybackTokenProvider (which took a LessonMedia
 * Eloquent model); it now consumes a storage-agnostic MediaAssetRef.
 */
interface PlaybackPort
{
    /**
     * @throws MediaUnavailableException when the asset cannot be signed (missing playback id / key).
     */
    public function issue(MediaAssetRef $asset, int $ttlSeconds): PlaybackToken;
}
