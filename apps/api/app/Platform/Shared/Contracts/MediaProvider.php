<?php

namespace App\Platform\Shared\Contracts;

/**
 * Higher-level media abstraction (e.g. video/image delivery). Returns opaque keys and signed
 * URLs; never exposes raw provider identifiers to callers. Contract only — no implementation.
 */
interface MediaProvider
{
    /** Upload media and return an opaque storage key. */
    public function upload(string $path, mixed $contents): string;

    /** Public (or CDN) URL for a key. */
    public function url(string $key): string;

    /** Short-lived signed URL for protected delivery. */
    public function temporaryUrl(string $key, int $ttlSeconds = 300): string;

    public function delete(string $key): bool;
}
