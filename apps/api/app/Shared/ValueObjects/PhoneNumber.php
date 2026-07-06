<?php

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable phone number value object, normalized toward E.164 (a leading + and digits).
 * Validation is intentionally lightweight (no country-specific business rules).
 */
final readonly class PhoneNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = preg_replace('/[\s\-()]/', '', trim($value)) ?? '';

        if (preg_match('/^\+?[1-9]\d{6,14}$/', $normalized) !== 1) {
            throw new InvalidArgumentException("Invalid phone number: {$value}");
        }

        $this->value = $normalized;
    }

    /** Return in E.164 form (guaranteed leading +). */
    public function e164(): string
    {
        return str_starts_with($this->value, '+') ? $this->value : '+'.$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->e164() === $other->e164();
    }

    public function __toString(): string
    {
        return $this->e164();
    }
}
