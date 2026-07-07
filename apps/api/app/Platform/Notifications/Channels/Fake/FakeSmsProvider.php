<?php

namespace App\Platform\Notifications\Channels\Fake;

use App\Platform\Notifications\Contracts\Providers\SmsProvider;
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
