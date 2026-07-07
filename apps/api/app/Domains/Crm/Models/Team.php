<?php

namespace App\Domains\Crm\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasPublicId;

    protected $table = 'crm_teams';

    protected $fillable = ['organization_id', 'department_id', 'name'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationMember::class, 'crm_team_members', 'team_id', 'member_id')->withPivot('role');
    }
}
