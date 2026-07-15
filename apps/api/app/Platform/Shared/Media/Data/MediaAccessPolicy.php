<?php

namespace App\Platform\Shared\Media\Data;

/**
 * Describes how a media asset must be accessed. Signing/TTL/visibility metadata only — carries
 * no storage identifiers. The effective TTL used for a given signature remains the explicit
 * argument passed to PlaybackPort::issue(); ttlSeconds here is descriptive.
 */
final readonly class MediaAccessPolicy
{
    public function __construct(
        public bool $signed = true,
        public string $visibility = 'private',
        public int $ttlSeconds = 0,
    ) {}
}
