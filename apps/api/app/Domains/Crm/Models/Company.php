<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Concerns\HasActivities;
use App\Domains\Crm\Concerns\HasNotes;
use App\Domains\Crm\Concerns\HasTags;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasActivities;
    use HasNotes;
    use HasPublicId;
    use HasTags;
    use SoftDeletes;

    protected $table = 'crm_companies';

    protected $fillable = ['organization_id', 'name', 'website', 'industry', 'size'];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
