<?php

use App\Domains\Live\Exceptions\InvalidTimezoneException;
use App\Domains\Live\Services\TimezoneService;

it('converts local wall-clock to UTC and back', function () {
    $svc = new TimezoneService;

    $utc = $svc->toUtc('2025-12-01 14:00', 'Asia/Riyadh'); // UTC+3
    expect($utc->format('H:i'))->toBe('11:00')
        ->and($svc->inZone($utc, 'Asia/Riyadh'))->toContain('T14:00:00');
});

it('rejects an invalid timezone', function () {
    (new TimezoneService)->assertValid('Mars/Phobos');
})->throws(InvalidTimezoneException::class);
