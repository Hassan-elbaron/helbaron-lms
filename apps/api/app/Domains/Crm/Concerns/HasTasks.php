<?php

namespace App\Domains\Crm\Concerns;

use App\Domains\Crm\Models\CrmTask;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTasks
{
    public function tasks(): MorphMany
    {
        return $this->morphMany(CrmTask::class, 'taskable');
    }
}
