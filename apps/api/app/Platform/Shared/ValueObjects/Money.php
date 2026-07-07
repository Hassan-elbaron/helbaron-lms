<?php

namespace App\Platform\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable money value object. Stores an integer amount in the currency's minor unit
 * (e.g. cents) to avoid floating-point drift. No pricing/business logic.
 */
final readonly class Money
{
    public function __construct(
        public int $minor,
        public string $currency,
    ) {
        if ($currency === '' || strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO code.');
        }
    }

    /** Build from a minor-unit integer (e.g. 1999 => 19.99). */
    public static function fromMinor(int $minor, string $currency): self
    {
        return new self($minor, strtoupper($currency));
    }

    /** Build from a major-unit decimal (e.g. 19.99), rounding to $scale minor digits. */
    public static function fromMajor(float $major, string $currency, int $scale = 2): self
    {
        return new self((int) round($major * (10 ** $scale)), strtoupper($currency));
    }

    public function major(int $scale = 2): float
    {
        return $this->minor / (10 ** $scale);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor + $other->minor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor - $other->minor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->minor === $other->minor && $this->currency === $other->currency;
    }

    public function format(int $scale = 2): string
    {
        return number_format($this->major($scale), $scale).' '.$this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot operate on different currencies.');
        }
    }
}
