<?php

namespace App\Platform\Media\Playback\Providers;

use App\Platform\Shared\Media\Contracts\PlaybackPort;
use App\Platform\Shared\Media\Data\MediaAssetRef;
use App\Platform\Shared\Media\Data\PlaybackToken;

/**
 * Default signer for local/test. Produces a deterministic signed URL WITHOUT revealing any raw
 * storage identifier. No real streaming (playback is out of scope). Consumes a MediaAssetRef;
 * identical output shape to the former Learning FakePlaybackTokenProvider.
 */
class FakePlaybackSigner implements PlaybackPort
{
    public function issue(MediaAssetRef $asset, int $ttlSeconds): PlaybackToken
    {
        $expires = now()->addSeconds($ttlSeconds);

        // Sign over an internal reference the client never sees.
        $reference = (string) ($asset->playbackId ?? $asset->storageKey ?? $asset->id);
        $signature = hash_hmac('sha256', $reference.'|'.$expires->getTimestamp(), (string) config('app.key'));

        $kind = $asset->playbackId !== null ? 'video' : 'file';

        return new PlaybackToken(
            url: url("/media/stream/{$signature}").'?expires='.$expires->getTimestamp(),
            expiresAt: $expires,
            kind: $kind,
        );
    }
}
