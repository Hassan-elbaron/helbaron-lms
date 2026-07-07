<?php

namespace App\Platform\Identity\Events;

use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MfaEnabled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly User $user) {}
}
