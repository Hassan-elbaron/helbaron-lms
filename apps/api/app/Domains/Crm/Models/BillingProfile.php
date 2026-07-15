<?php

namespace App\Domains\Crm\Models;

use App\Platform\Shared\Tenancy\Concerns\BelongsToTenant;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingProfile extends Model
{
    use BelongsToTenant;
    use HasPublicId;

    protected $fillable = ['organization_id', 'legal_name', 'tax_id', 'address', 'country'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
