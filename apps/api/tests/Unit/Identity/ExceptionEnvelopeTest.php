<?php

use App\Domains\Identity\Exceptions\AccountLockedException;
use App\Domains\Identity\Exceptions\ExpiredOtpException;
use App\Domains\Identity\Exceptions\InvalidCredentialsException;
use App\Domains\Identity\Exceptions\InvalidOtpException;
use App\Domains\Identity\Exceptions\OtpRateLimitedException;

it('renders identity exceptions to the standard envelope', function () {
    $req = request();

    expect((new InvalidCredentialsException)->render($req)->getStatusCode())->toBe(401)
        ->and((new InvalidOtpException)->render($req)->getStatusCode())->toBe(422)
        ->and((new ExpiredOtpException)->render($req)->getStatusCode())->toBe(410)
        ->and((new AccountLockedException)->render($req)->getStatusCode())->toBe(423);

    $payload = (new InvalidCredentialsException)->render($req)->getData(true);
    expect($payload)->toHaveKey('error')
        ->and($payload['error']['code'])->toBe('AUTH_INVALID_CREDENTIALS')
        ->and($payload['error'])->toHaveKeys(['code', 'message', 'details', 'correlation_id', 'timestamp']);
});

it('includes retry_after on rate-limit errors', function () {
    $payload = (new OtpRateLimitedException(3600))->render(request())->getData(true);

    expect($payload['error']['code'])->toBe('AUTH_OTP_RATE_LIMITED')
        ->and($payload['error']['details']['retry_after'])->toBe(3600);
});
