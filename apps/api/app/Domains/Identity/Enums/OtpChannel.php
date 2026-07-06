<?php

namespace App\Domains\Identity\Enums;

enum OtpChannel: string
{
    case Email = 'email';
    case Sms = 'sms';

    public function configKey(): string
    {
        return "identity.otp.{$this->value}";
    }
}
