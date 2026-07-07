<?php

namespace App\Platform\Notifications\Channels\Fake;

use App\Platform\Notifications\Contracts\Providers\MailProvider;
use Illuminate\Support\Facades\Log;

/**
 * Fake email provider — records intent, never sends. No SES/Mailgun.
 */
class FakeMailProvider implements MailProvider
{
    public function send(string $to, string $subject, string $body): void
    {
        if (app()->environment('local')) {
            Log::info('[FAKE MAIL] would send', ['to' => $to, 'subject' => $subject]);
        }
    }
}
