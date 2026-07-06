<?php

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable duration value object stored as a non-negative integer number of seconds.
 */
final readonly class Duration
{
    public function __construct(public int $seconds)
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException('Duration cannot be negative.');
        }
    }

    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds);
    }

    public static function fromMinutes(int|float $minutes): self
    {
        return new self((int) round($minutes * 60));
    }

    public static function fromHours(int|float $hours): self
    {
        return new self((int) round($hours * 3600));
    }

    public function minutes(): float
    {
        return $this->seconds / 60;
    }

    public function hours(): float
    {
        return $this->seconds / 3600;
    }

    /** Format as H:MM:SS (hours omitted when zero → MM:SS). */
    public function format(): string
    {
        $h = intdiv($this->seconds, 3600);
        $m = intdiv($this->seconds % 3600, 60);
        $s = $this->seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }
}
