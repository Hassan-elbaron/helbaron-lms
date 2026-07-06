<?php

namespace App\Domains\Notifications\Contracts\Providers;

interface MailProvider
{
    public function send(string $to, string $subject, string $body): void;
}
