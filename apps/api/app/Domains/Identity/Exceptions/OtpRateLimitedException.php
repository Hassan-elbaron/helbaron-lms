<?php

namespace App\Domains\Identity\Exceptions;

class OtpRateLimitedException extends IdentityException
{
    protected string $errorCode = 'AUTH_OTP_RATE_LIMITED';

    protected int $status = 429;

    public function __construct(int $retryAfterSeconds = 60)
    {
        parent::__construct('Too many code requests. Please try again later.', [
            'retry_after' => $retryAfterSeconds,
        ]);
    }
}
