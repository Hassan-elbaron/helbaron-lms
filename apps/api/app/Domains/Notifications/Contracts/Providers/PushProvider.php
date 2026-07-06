<?php

namespace App\Domains\Notifications\Contracts\Providers;

interface PushProvider
{
    public function send(string $to, string $title, string $body): void;
}
