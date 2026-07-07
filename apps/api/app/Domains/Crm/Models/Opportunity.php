<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\OpportunityStatus;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasPublicId;
    use SoftDeletes;

    protected $table = 'crm_opportunities';

    protected $fillable = ['lead_id', 'company_id', 'name', 'amount_minor', 'currency', 'status', 'expected_close_date'];

    protected function casts(): array
    {
        return ['status' => OpportunityStatus::class, 'amount_minor' => 'integer', 'expected_close_date' => 'date'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
