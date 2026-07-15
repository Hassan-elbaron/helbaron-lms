<?php

namespace App\Platform\Media\Playback\Providers;

use App\Platform\Shared\Media\Contracts\PlaybackPort;
use App\Platform\Shared\Media\Data\MediaAssetRef;
use App\Platform\Shared\Media\Data\PlaybackToken;
use App\Platform\Shared\Media\Exceptions\MediaUnavailableException;
use App\Platform\Shared\Support\CloudFrontUrlSigner;

/**
 * Issues a short-lived CloudFront signed URL from the media storage key. The key is used only to
 * build the signed URL server-side and is never returned to the caller. Consumes a MediaAssetRef;
 * identical output to the former Learning CloudFrontPlaybackTokenProvider.
 */
class CloudFrontPlaybackSigner implements PlaybackPort
{
    public function __construct(private readonly CloudFrontUrlSigner $signer) {}

    public function issue(MediaAssetRef $asset, int $ttlSeconds): PlaybackToken
    {
        if ($asset->storageKey === null) {
            throw new MediaUnavailableException;
        }

        $expires = now()->addSeconds($ttlSeconds);
        $url = $this->signer->sign($asset->storageKey, $expires->getTimestamp());

        return new PlaybackToken(url: $url, expiresAt: $expires, kind: 'file');
    }
}
