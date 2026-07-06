<?php

namespace App\Domains\Certification\Events;

use App\Domains\Certification\Models\BadgeAward;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeAwarded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly BadgeAward $award) {}
}
