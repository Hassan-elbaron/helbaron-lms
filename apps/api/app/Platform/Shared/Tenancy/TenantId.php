<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * Immutable identifier of a tenant (the isolatable customer/organization unit).
 *
 * Value object only — no persistence, no business logic. The concrete meaning of the value
 * (currently an organization id) is resolved by a TenantResolver; this type just carries it.
 */
final class TenantId
{
    private function __construct(
        public readonly int|string $value,
    ) {}

    public static function from(int|string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return (string) $this->value === (string) $other->value;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
