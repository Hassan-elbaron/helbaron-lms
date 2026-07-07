<?php

namespace App\Contexts\Learning\Playback\Providers;

use App\Domains\Authoring\Models\LessonMedia;
use App\Contexts\Learning\Contracts\PlaybackTokenProvider;
use App\Contexts\Learning\Playback\Data\PlaybackToken;

/**
 * Default provider for local/test. Produces a deterministic signed URL WITHOUT revealing any
 * raw storage identifier. No real streaming (playback is out of scope).
 */
class FakePlaybackTokenProvider implements PlaybackTokenProvider
{
    public function issue(LessonMedia $media, int $ttlSeconds): PlaybackToken
    {
        $expires = now()->addSeconds($ttlSeconds);

        // Sign over an internal reference the client never sees.
        $reference = (string) ($media->mux_playback_id ?? $media->s3_key ?? $media->id);
        $signature = hash_hmac('sha256', $reference.'|'.$expires->getTimestamp(), (string) config('app.key'));

        $kind = $media->mux_playback_id !== null ? 'video' : 'file';

        return new PlaybackToken(
            url: url("/media/stream/{$signature}").'?expires='.$expires->getTimestamp(),
            expiresAt: $expires,
            kind: $kind,
        );
    }
}
