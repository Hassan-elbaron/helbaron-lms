<?php

namespace App\Domains\Identity\Listeners;

use App\Domains\Identity\Enums\OtpChannel;
use App\Domains\Identity\Events\UserRegistered;
use App\Domains\Identity\Services\OtpService;

/**
 * On registration, issue an email verification OTP. Decouples registration from delivery.
 */
class SendEmailOtpOnRegistration
{
    public function __construct(private readonly OtpService $otp) {}

    public function handle(UserRegistered $event): void
    {
        $this->otp->send($event->user, OtpChannel::Email, $event->user->email);
    }
}
