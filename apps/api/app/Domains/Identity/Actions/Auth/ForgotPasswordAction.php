<?php

namespace App\Domains\Identity\Actions\Auth;

use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Password;

class ForgotPasswordAction extends BaseAction
{
    /**
     * Send a reset link (via the user's overridden ResetPasswordNotification). Always returns
     * without revealing whether the email exists (no account enumeration).
     */
    public function execute(string $email): void
    {
        Password::broker()->sendResetLink(['email' => $email]);
    }
}
