<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Lifecycle;

/**
 * Immutable per-tenant settings bag (A2-S04 foundation). Descriptive key/value configuration for a
 * tenant. Persistence is provided later by Administration.
 */
final class TenantSettings
{
    /** @param array<string, mixed> $values */
    public function __construct(
        private readonly array $values = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->values;
    }

    public function with(string $key, mixed $value): self
    {
        return new self([...$this->values, $key => $value]);
    }
}
