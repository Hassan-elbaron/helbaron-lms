<?php

namespace App\Domains\Identity\Actions\Auth;

use App\Domains\Identity\Enums\OtpChannel;
use App\Domains\Identity\Events\PhoneVerified;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\OtpService;
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
