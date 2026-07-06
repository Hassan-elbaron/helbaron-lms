<?php

namespace App\Domains\Identity\Actions\Auth;

use App\Domains\Identity\Enums\OtpChannel;
use App\Domains\Identity\Events\EmailVerified;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\OtpService;
use App\Shared\Actions\BaseAction;

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
