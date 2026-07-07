<?php

namespace App\Platform\Notifications\Contracts\Providers;

interface MailProvider
{
    public function send(string $to, string $subject, string $body): void;
}
