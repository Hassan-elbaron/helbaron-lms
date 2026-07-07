<?php

use App\Platform\Shared\ValueObjects\EmailAddress;

it('normalizes and validates emails', function () {
    $e = new EmailAddress('  Test@Example.COM ');
    expect($e->value)->toBe('test@example.com')
        ->and($e->domain())->toBe('example.com')
        ->and((string) $e)->toBe('test@example.com');
});

it('rejects an invalid email', function () {
    new EmailAddress('not-an-email');
})->throws(InvalidArgumentException::class);
