<?php

namespace App\Domains\Crm\Models;

use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmNote extends Model
{
    use HasPublicId;

    protected $fillable = ['noteable_type', 'noteable_id', 'user_id', 'body'];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
