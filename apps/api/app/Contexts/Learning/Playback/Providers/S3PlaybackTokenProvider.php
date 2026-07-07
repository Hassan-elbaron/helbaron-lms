<?php

namespace App\Contexts\Learning\Playback\Providers;

use App\Domains\Authoring\Models\LessonMedia;
use App\Contexts\Learning\Contracts\PlaybackTokenProvider;
use App\Contexts\Learning\Exceptions\MediaUnavailableException;
use App\Contexts\Learning\Playback\Data\PlaybackToken;
use Illuminate\Support\Facades\Storage;

/**
 * Issues a short-lived signed S3/CloudFront URL from the media s3_key. The key itself is used
 * only server-side to sign — it is never returned to the caller.
 */
class S3PlaybackTokenProvider implements PlaybackTokenProvider
{
    public function issue(LessonMedia $media, int $ttlSeconds): PlaybackToken
    {
        if ($media->s3_key === null) {
            throw new MediaUnavailableException;
        }

        $expires = now()->addSeconds($ttlSeconds);
        $url = Storage::disk('s3')->temporaryUrl($media->s3_key, $expires);

        return new PlaybackToken(url: $url, expiresAt: $expires, kind: 'file');
    }
}
