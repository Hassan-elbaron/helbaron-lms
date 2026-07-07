<?php

namespace App\Platform\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable percentage value object stored on a 0..100 scale.
 */
final readonly class Percentage
{
    public function __construct(public float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Percentage cannot be negative.');
        }
    }

    /** Build from a 0..1 fraction (e.g. 0.25 => 25%). */
    public static function fromFraction(float $fraction): self
    {
        return new self($fraction * 100);
    }

    /** Return as a 0..1 fraction. */
    public function toFraction(): float
    {
        return $this->value / 100;
    }

    /** Apply this percentage to a number. */
    public function of(float $amount): float
    {
        return $amount * $this->toFraction();
    }

    public function format(int $decimals = 0): string
    {
        return number_format($this->value, $decimals).'%';
    }
}
