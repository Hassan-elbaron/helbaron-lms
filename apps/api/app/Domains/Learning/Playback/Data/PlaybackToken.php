<?php

namespace App\Domains\Learning\Playback\Data;

use Carbon\CarbonInterface;

/**
 * A short-lived, signed media URL. Deliberately opaque: it exposes NO raw storage identifiers
 * (s3_key / mux_asset_id) to callers.
 */
final readonly class PlaybackToken
{
    public function __construct(
        public string $url,
        public CarbonInterface $expiresAt,
        public string $kind, // e.g. 'video', 'file'
    ) {}
}
