<?php

namespace App\Domains\Crm\Concerns;

use App\Domains\Crm\Models\CrmNote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotes
{
    public function notes(): MorphMany
    {
        return $this->morphMany(CrmNote::class, 'noteable')->latest('id');
    }
}
