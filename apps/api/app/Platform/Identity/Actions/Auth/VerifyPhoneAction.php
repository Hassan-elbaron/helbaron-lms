<?php

namespace App\Platform\Identity\Actions\Auth;

use App\Platform\Identity\Enums\OtpChannel;
use App\Platform\Identity\Events\PhoneVerified;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Services\OtpService;
use App\Platform\Shared\Actions\BaseAction;

class VerifyPhoneAction extends BaseAction
{
    public function __construct(private readonly OtpService $otp) {}

    public function execute(User $user, string $code): void
    {
        $this->otp->verify($user, OtpChannel::Sms, (string) $user->phone, $code);
        $user->markPhoneVerified();

        PhoneVerified::dispatch($user);
    }
}
