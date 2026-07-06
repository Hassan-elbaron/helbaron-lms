<?php

namespace App\Domains\Identity\Listeners;

use App\Domains\Identity\Enums\OtpChannel;
use App\Domains\Identity\Events\UserRegistered;
use App\Domains\Identity\Services\OtpService;

/**
 * On registration, issue a phone verification OTP when a phone number was provided.
 */
class SendPhoneOtpOnRegistration
{
    public function __construct(private readonly OtpService $otp) {}

    public function handle(UserRegistered $event): void
    {
        if (! empty($event->user->phone)) {
            $this->otp->send($event->user, OtpChannel::Sms, $event->user->phone);
        }
    }
}
