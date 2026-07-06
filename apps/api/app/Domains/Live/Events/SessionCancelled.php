<?php

namespace App\Domains\Live\Events;

use App\Domains\Live\Models\LiveSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly LiveSession $session) {}
}
