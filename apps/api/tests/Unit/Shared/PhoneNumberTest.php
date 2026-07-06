<?php

use App\Shared\ValueObjects\PhoneNumber;

it('normalizes to E.164', function () {
    expect((new PhoneNumber('+966 50 123 4567'))->e164())->toBe('+966501234567')
        ->and((new PhoneNumber('966501234567'))->e164())->toBe('+966501234567');
});

it('rejects an invalid phone number', function () {
    new PhoneNumber('abc123');
})->throws(InvalidArgumentException::class);
