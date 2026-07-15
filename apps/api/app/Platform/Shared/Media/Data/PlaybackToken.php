<?php

namespace App\Platform\Shared\Media\Data;

use Carbon\CarbonInterface;

/**
 * A short-lived, signed media URL. Deliberately opaque: it exposes NO raw storage identifiers
 * (s3_key / mux_asset_id) to callers. Relocated from Learning to the Shared Media namespace so
 * both Learning (consumer) and the Media platform (producer) may reference it. Shape unchanged.
 */
final readonly class PlaybackToken
{
    public function __construct(
        public string $url,
        public CarbonInterface $expiresAt,
        public string $kind, // e.g. 'video', 'file'
    ) {}
}
