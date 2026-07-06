<?php

namespace App\Domains\Notifications\Contracts\Providers;

interface SmsProvider
{
    public function send(string $to, string $body): void;
}
