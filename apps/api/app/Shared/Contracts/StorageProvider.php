<?php

namespace App\Shared\Contracts;

/**
 * Low-level object storage abstraction (e.g. S3). Concrete adapters live outside the shared
 * foundation; this only declares the contract. No vendor coupling here.
 */
interface StorageProvider
{
    public function put(string $path, mixed $contents): string;

    public function get(string $path): ?string;

    public function exists(string $path): bool;

    public function url(string $path): string;

    public function temporaryUrl(string $path, int $ttlSeconds = 300): string;

    public function delete(string $path): bool;
}
