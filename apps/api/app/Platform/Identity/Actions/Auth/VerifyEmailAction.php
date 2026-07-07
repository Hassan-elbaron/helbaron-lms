<?php

namespace App\Platform\Identity\Actions\Auth;

use App\Platform\Identity\Enums\OtpChannel;
use App\Platform\Identity\Events\EmailVerified;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Services\OtpService;
use App\Platform\Shared\Actions\BaseAction;

class VerifyEmailAction extends BaseAction
{
    public function __construct(private readonly OtpService $otp) {}

    public function execute(User $user, string $code): void
    {
        $this->otp->verify($user, OtpChannel::Email, $user->email, $code);
        $user->markEmailVerified();

        EmailVerified::dispatch($user);
    }
}
