<?php

use App\Shared\Enums\Locale;
use App\Shared\Enums\Status;
use App\Shared\Enums\Visibility;

it('exposes status helpers', function () {
    expect(Status::Active->isActive())->toBeTrue()
        ->and(Status::Draft->isActive())->toBeFalse()
        ->and(Status::values())->toContain('active', 'draft', 'inactive', 'archived');
});

it('reports locale direction', function () {
    expect(Locale::Ar->isRtl())->toBeTrue()
        ->and(Locale::En->direction())->toBe('ltr')
        ->and(Locale::Ar->label())->toBe('العربية');
});

it('exposes visibility helpers', function () {
    expect(Visibility::Public->isPublic())->toBeTrue()
        ->and(Visibility::values())->toContain('public', 'private', 'unlisted', 'hidden');
});
