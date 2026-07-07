<?php

namespace App\Platform\Notifications\Contracts\Providers;

interface PushProvider
{
    public function send(string $to, string $title, string $body): void;
}
