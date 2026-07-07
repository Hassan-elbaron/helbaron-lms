<?php

namespace App\Platform\Identity\Listeners;

use App\Platform\Identity\Enums\OtpChannel;
use App\Platform\Identity\Events\UserRegistered;
use App\Platform\Identity\Services\OtpService;

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
