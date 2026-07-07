<?php

namespace App\Platform\Notifications\Contracts\Providers;

interface SmsProvider
{
    public function send(string $to, string $body): void;
}
