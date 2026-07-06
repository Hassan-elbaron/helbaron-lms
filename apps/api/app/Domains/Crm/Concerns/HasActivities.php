<?php

namespace App\Domains\Crm\Concerns;

use App\Domains\Crm\Models\CrmActivity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivities
{
    public function activities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject')->latest('occurred_at');
    }
}
