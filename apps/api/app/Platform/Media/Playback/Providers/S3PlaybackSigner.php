<?php

namespace App\Platform\Media\Playback\Providers;

use App\Platform\Shared\Media\Contracts\PlaybackPort;
use App\Platform\Shared\Media\Data\MediaAssetRef;
use App\Platform\Shared\Media\Data\PlaybackToken;
use App\Platform\Shared\Media\Exceptions\MediaUnavailableException;
use Illuminate\Support\Facades\Storage;

/**
 * Issues a short-lived signed S3 URL from the media storage key. The key itself is used only
 * server-side to sign — it is never returned to the caller. Consumes a MediaAssetRef; identical
 * output to the former Learning S3PlaybackTokenProvider.
 */
class S3PlaybackSigner implements PlaybackPort
{
    public function issue(MediaAssetRef $asset, int $ttlSeconds): PlaybackToken
    {
        if ($asset->storageKey === null) {
            throw new MediaUnavailableException;
        }

        $expires = now()->addSeconds($ttlSeconds);
        $url = Storage::disk('s3')->temporaryUrl($asset->storageKey, $expires);

        return new PlaybackToken(url: $url, expiresAt: $expires, kind: 'file');
    }
}
