<?php

use App\Platform\Shared\ValueObjects\Percentage;

it('converts between fraction and percent', function () {
    expect(Percentage::fromFraction(0.25)->value)->toBe(25.0)
        ->and((new Percentage(50))->toFraction())->toBe(0.5)
        ->and((new Percentage(10))->of(200.0))->toBe(20.0)
        ->and((new Percentage(12.5))->format(1))->toBe('12.5%');
});

it('rejects a negative percentage', function () {
    new Percentage(-1);
})->throws(InvalidArgumentException::class);
