<?php

namespace App\Contexts\Learning\Playback\Providers;

use App\Domains\Authoring\Models\LessonMedia;
use App\Contexts\Learning\Contracts\PlaybackTokenProvider;
use App\Contexts\Learning\Exceptions\MediaUnavailableException;
use App\Contexts\Learning\Playback\Data\PlaybackToken;
use App\Platform\Shared\Support\CloudFrontUrlSigner;

/**
 * Issues a short-lived CloudFront signed URL from the media s3_key. The key is used only to
 * build the signed URL server-side and is never returned to the caller.
 */
class CloudFrontPlaybackTokenProvider implements PlaybackTokenProvider
{
    public function __construct(private readonly CloudFrontUrlSigner $signer) {}

    public function issue(LessonMedia $media, int $ttlSeconds): PlaybackToken
    {
        if ($media->s3_key === null) {
            throw new MediaUnavailableException;
        }

        $expires = now()->addSeconds($ttlSeconds);
        $url = $this->signer->sign($media->s3_key, $expires->getTimestamp());

        return new PlaybackToken(url: $url, expiresAt: $expires, kind: 'file');
    }
}
