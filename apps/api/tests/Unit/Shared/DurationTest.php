<?php

use App\Platform\Shared\ValueObjects\Duration;

it('builds from units and formats', function () {
    expect(Duration::fromMinutes(90)->seconds)->toBe(5400)
        ->and(Duration::fromHours(1)->seconds)->toBe(3600)
        ->and(Duration::fromSeconds(5400)->format())->toBe('1:30:00')
        ->and(Duration::fromSeconds(90)->format())->toBe('1:30');
});

it('rejects a negative duration', function () {
    new Duration(-5);
})->throws(InvalidArgumentException::class);
