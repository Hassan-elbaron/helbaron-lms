<?php

namespace App\Platform\Identity\Listeners;

use App\Platform\Identity\Enums\OtpChannel;
use App\Platform\Identity\Events\UserRegistered;
use App\Platform\Identity\Services\OtpService;

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
