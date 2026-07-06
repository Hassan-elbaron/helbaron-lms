<?php

namespace App\Domains\Notifications\Channels\Fake;

use App\Domains\Notifications\Contracts\Providers\SmsProvider;
use Illuminate\Support\Facades\Log;

/**
 * Fake SMS provider — never sends. No Twilio.
 */
class FakeSmsProvider implements SmsProvider
{
    public function send(string $to, string $body): void
    {
        if (app()->environment('local')) {
            Log::info('[FAKE SMS] would send', ['to' => $to]);
        }
    }
}
