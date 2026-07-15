<?php

namespace App\Platform\Shared\Media\Data;

/**
 * An immutable, storage-agnostic reference to a signable media asset. This is the ONLY media
 * shape that crosses a context boundary: it carries the public playback id / storage key needed
 * to sign a URL server-side, but never the raw provider asset id (e.g. Mux asset id). It is used
 * only inside the port boundary (Learning -> PlaybackPort) and is never serialized to a client.
 */
final readonly class MediaAssetRef
{
    /**
     * @param  string  $id  Stable, non-sequential asset identifier (LessonMedia public id).
     * @param  string  $provider  Storage backend hint: 'mux' | 's3'.
     * @param  ?string  $playbackId  Public playback id (Mux mux_playback_id) — never the asset id.
     * @param  ?string  $storageKey  Object-storage key (s3_key) — used only server-side to sign.
     * @param  array<string, mixed>  $metadata  Non-sensitive extras (e.g. filesize). Never the mux asset id.
     */
    public function __construct(
        public string $id,
        public string $provider,
        public ?string $playbackId,
        public ?string $storageKey,
        public ?string $mimeType,
        public ?int $durationSeconds,
        public MediaAccessPolicy $policy,
        public array $metadata = [],
    ) {}
}
