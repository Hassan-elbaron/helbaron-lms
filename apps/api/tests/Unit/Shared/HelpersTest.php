<?php

use App\Shared\Helpers\Slug;
use App\Shared\Helpers\Uuid;

it('generates and validates uuids', function () {
    $id = Uuid::v7();
    expect(Uuid::isValid($id))->toBeTrue()
        ->and(Uuid::isValid('not-a-uuid'))->toBeFalse();
});

it('makes slugs and unique slugs', function () {
    expect(Slug::make('Hello World'))->toBe('hello-world');

    $taken = ['hello-world'];
    $unique = Slug::unique('Hello World', fn (string $s) => in_array($s, $taken, true));
    expect($unique)->toBe('hello-world-2');
});
