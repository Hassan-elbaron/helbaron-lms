<?php

use App\Shared\ValueObjects\Money;

it('builds from minor and major units', function () {
    expect(Money::fromMinor(1999, 'sar')->minor)->toBe(1999)
        ->and(Money::fromMinor(1999, 'sar')->currency)->toBe('SAR')
        ->and(Money::fromMajor(19.99, 'SAR')->minor)->toBe(1999)
        ->and(Money::fromMinor(1999, 'SAR')->major())->toBe(19.99);
});

it('adds and subtracts same-currency amounts', function () {
    $a = Money::fromMinor(1000, 'SAR');
    $b = Money::fromMinor(500, 'SAR');
    expect($a->add($b)->minor)->toBe(1500)
        ->and($a->subtract($b)->minor)->toBe(500)
        ->and($a->equals(Money::fromMinor(1000, 'SAR')))->toBeTrue();
});

it('rejects mismatched currencies', function () {
    Money::fromMinor(1000, 'SAR')->add(Money::fromMinor(1000, 'USD'));
})->throws(InvalidArgumentException::class);

it('rejects an invalid currency code', function () {
    new Money(100, 'SARX');
})->throws(InvalidArgumentException::class);
