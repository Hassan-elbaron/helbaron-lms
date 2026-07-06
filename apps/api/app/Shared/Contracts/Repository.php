<?php

namespace App\Shared\Contracts;

/**
 * Generic repository contract. Deliberately framework-agnostic (object, not Model) so any
 * persistence strategy can implement it. No business logic.
 */
interface Repository
{
    public function find(int|string $id): ?object;

    public function findByPublicId(string $publicId): ?object;

    /** @return iterable<int, object> */
    public function all(): iterable;

    /** @param array<string, mixed> $data */
    public function create(array $data): object;

    /** @param array<string, mixed> $data */
    public function update(object $model, array $data): object;

    public function delete(object $model): bool;
}
