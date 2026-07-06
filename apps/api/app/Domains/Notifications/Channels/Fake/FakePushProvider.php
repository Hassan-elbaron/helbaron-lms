<?php

namespace App\Domains\Notifications\Channels\Fake;

use App\Domains\Notifications\Contracts\Providers\PushProvider;
use Illuminate\Support\Facades\Log;

/**
 * Fake push provider — never sends. No Firebase.
 */
class FakePushProvider implements PushProvider
{
    public function send(string $to, string $title, string $body): void
    {
        if (app()->environment('local')) {
            Log::info('[FAKE PUSH] would send', ['to' => $to, 'title' => $title]);
        }
    }
}
