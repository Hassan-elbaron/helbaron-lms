<?php

namespace App\Domains\Crm\Models;

use App\Platform\Shared\Traits\HasPublicId;
use App\Platform\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class CrmTag extends Model
{
    use HasPublicId;
    use HasSlug;

    protected $fillable = ['name', 'slug'];
}
